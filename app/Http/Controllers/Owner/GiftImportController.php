<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\CsvCell;
use App\Domain\Wishlist\GiftLimitGuard;
use App\Domain\Wishlist\Models\Gift;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bulk CSV import for gifts. Owner pastes 30 rows in one go
 * instead of opening the drawer 30 times.
 *
 * CSV format (first row = headers, accepted PL or EN):
 *   tytuł, opis, cena, link, priorytet
 *   title, description, price, url, priority
 *
 * Priorities: `1|2|3` or PL words "muszę mieć / chcę / fajnie byłoby".
 * Anything else falls back to 2 (normalny).
 *
 * Rows are inserted in a single tx, respecting the active package
 * gift_limit — overflow rows are reported as skipped instead of
 * partially-applied.
 */
final class GiftImportController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $current,
        private readonly GiftLimitGuard $limit,
    ) {}

    public function show(Request $request, Tenant $tenant): View
    {
        $this->authorizeTenant($request, $tenant);

        return view('owner.gifts.import', ['tenant' => $tenant]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorizeTenant($request, $tenant);
        $this->current->set($tenant);

        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:512'], // 512 KB
        ]);

        $package = $this->limit->activePackage($tenant);
        if ($package === null) {
            return back()->withErrors(['csv' => 'Brak aktywnego pakietu — przedłuż subskrypcję, aby importować.']);
        }

        $rows = $this->parse($request->file('csv')->getRealPath());
        if ($rows === []) {
            return back()->withErrors(['csv' => 'Plik CSV jest pusty albo nie zawiera kolumny „tytuł"/„title".']);
        }

        $remaining = $package->gift_limit === null
            ? PHP_INT_MAX
            : max(0, $package->gift_limit - Gift::query()->where('tenant_id', $tenant->id)->count());

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $tenant, $remaining, &$imported, &$skipped): void {
            // Re-read max(position) INSIDE the tx so a concurrent
            // GiftController::store can't write a colliding position.
            // Pos counter advances only for rows we actually insert so
            // no holes are left when the limit cuts the import short.
            $nextPos = (int) Gift::query()->where('tenant_id', $tenant->id)->max('position') + 1;

            foreach ($rows as $row) {
                if ($imported >= $remaining) {
                    $skipped++;

                    continue;
                }
                Gift::create([
                    'tenant_id' => $tenant->id,
                    'title' => mb_substr(CsvCell::sanitiseImport($row['title']) ?? '', 0, 120),
                    'description' => $row['description'] === null
                        ? null
                        : mb_substr(CsvCell::sanitiseImport($row['description']) ?? '', 0, 2000),
                    'url' => $this->safeUrl($row['url']),
                    'price_pln_gr' => $row['price_gr'],
                    'priority' => $row['priority'],
                    'status' => Gift::STATUS_AVAILABLE,
                    'position' => $nextPos++,
                ]);
                $imported++;
            }
        });

        $msg = "Zaimportowano $imported prezentów.";
        if ($skipped > 0) {
            $msg .= " $skipped pominięto (limit pakietu).";
        }

        return redirect()
            ->route('owner.gifts.index', $tenant)
            ->with('status', $msg);
    }

    /**
     * Read the uploaded CSV and normalise each row to a strict shape.
     * First row must be a header. Accepts comma OR semicolon (Excel PL).
     *
     * @return list<array{title:string, description:?string, url:?string, price_gr:?int, priority:int}>
     */
    private function parse(string $path): array
    {
        $fh = fopen($path, 'r');
        if ($fh === false) {
            return [];
        }

        $first = fgets($fh);
        if ($first === false) {
            fclose($fh);

            return [];
        }
        $delim = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        rewind($fh);

        $headers = fgetcsv($fh, 0, $delim) ?: [];
        $headers = array_map(static fn ($h) => strtolower(trim((string) $h)), $headers);
        $map = $this->headerMap($headers);
        if (! isset($map['title'])) {
            fclose($fh);

            return [];
        }

        $out = [];
        while (($line = fgetcsv($fh, 0, $delim)) !== false) {
            $title = trim((string) ($line[$map['title']] ?? ''));
            if ($title === '') {
                continue;
            }
            $out[] = [
                'title' => $title,
                'description' => $this->take($line, $map, 'description'),
                'url' => $this->take($line, $map, 'url'),
                'price_gr' => $this->parsePrice($this->take($line, $map, 'price')),
                'priority' => $this->parsePriority($this->take($line, $map, 'priority')),
            ];
            if (count($out) >= 500) {
                break; // safety cap matching the gift_limit ceiling
            }
        }
        fclose($fh);

        return $out;
    }

    /** @param  list<string>  $headers @return array<string,int> */
    private function headerMap(array $headers): array
    {
        $aliases = [
            'title' => ['title', 'tytul', 'tytuł', 'nazwa'],
            'description' => ['description', 'opis'],
            'url' => ['url', 'link', 'adres', 'sklep'],
            'price' => ['price', 'cena', 'cena (zl)', 'cena_pln'],
            'priority' => ['priority', 'priorytet', 'wazne', 'ważne'],
        ];
        $map = [];
        foreach ($aliases as $key => $names) {
            foreach ($headers as $i => $h) {
                if (in_array($h, $names, true)) {
                    $map[$key] = $i;
                    break;
                }
            }
        }

        return $map;
    }

    /** @param  list<?string>  $row @param  array<string,int>  $map */
    private function take(array $row, array $map, string $key): ?string
    {
        if (! isset($map[$key])) {
            return null;
        }
        $v = trim((string) ($row[$map[$key]] ?? ''));

        return $v === '' ? null : $v;
    }

    /** Accepts "299,99" / "299.99" / "299" / "299 zł". Returns groszy. */
    private function parsePrice(?string $raw): ?int
    {
        if ($raw === null) {
            return null;
        }
        $clean = preg_replace('/[^\d.,]/', '', $raw) ?? '';
        $clean = str_replace(',', '.', $clean);
        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        return (int) round((float) $clean * 100);
    }

    private function parsePriority(?string $raw): int
    {
        $raw = trim(strtolower((string) $raw));
        if ($raw === '') {
            return 2;
        }
        // Exact digit match wins — handles plain "1"/"2"/"3" without
        // accidentally promoting "13" to priority 1.
        if (preg_match('/^[123]$/', $raw) === 1) {
            return (int) $raw;
        }
        if (str_contains($raw, 'muszę') || str_contains($raw, 'musze') || str_contains($raw, 'high')) {
            return 1;
        }
        if (str_contains($raw, 'fajnie') || str_contains($raw, 'low')) {
            return 3;
        }

        return 2;
    }

    private function safeUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }
        $url = mb_substr($url, 0, 1024);

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    private function authorizeTenant(Request $request, Tenant $tenant): void
    {
        $user = $request->user();
        if ($user === null || ! $user->ownsTenant($tenant)) {
            abort(403);
        }
    }
}
