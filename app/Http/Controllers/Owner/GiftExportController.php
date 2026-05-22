<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV export of gifts for the Pro package (and above — pro is the
 * only tier today with `export` feature). Streams so memory usage
 * stays constant regardless of list size.
 */
final class GiftExportController extends Controller
{
    public function __construct(private readonly CurrentTenant $current) {}

    public function csv(Request $request, Tenant $tenant): StreamedResponse
    {
        $user = $request->user();
        if ($user === null || ! $user->ownsTenant($tenant)) {
            abort(403);
        }

        // Feature gate — the active subscription's package must allow export.
        $active = $tenant->subscriptions()
            ->where('status', 'active')
            ->with('package')
            ->orderByDesc('paid_at')
            ->first();

        if ($active === null || ! ($active->package?->hasFeature('export') ?? false)) {
            abort(403, 'Eksport CSV dostępny w pakiecie Pro i wyższych.');
        }

        $this->current->set($tenant);

        $filename = sprintf('dajprezent-%s-%s.csv', $tenant->slug, now()->format('Y-m-d'));

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            // UTF-8 BOM so Excel opens it correctly with Polish characters.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tytuł', 'Opis', 'Link', 'Cena (zł)', 'Priorytet', 'Status', 'Kategoria', 'Dodano'], ';');

            Gift::query()
                ->orderBy('position')
                ->orderByDesc('id')
                ->chunk(200, function ($chunk) use ($out): void {
                    foreach ($chunk as $gift) {
                        fputcsv($out, [
                            $gift->title,
                            $gift->description ?? '',
                            $gift->url ?? '',
                            $gift->price_pln_gr !== null ? number_format($gift->price_pln_gr / 100, 2, ',', '') : '',
                            match ($gift->priority) {
                                1 => 'muszę mieć',
                                2 => 'normalny',
                                3 => 'nice to have',
                                default => (string) $gift->priority,
                            },
                            match ($gift->status) {
                                Gift::STATUS_AVAILABLE => 'dostępny',
                                Gift::STATUS_RESERVED => 'zarezerwowany',
                                Gift::STATUS_RECEIVED => 'otrzymany',
                                default => $gift->status,
                            },
                            $gift->category ?? '',
                            $gift->created_at?->format('Y-m-d') ?? '',
                        ], ';');
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
