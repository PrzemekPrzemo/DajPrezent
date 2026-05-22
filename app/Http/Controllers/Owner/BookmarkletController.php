<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Dodaj z dowolnego sklepu" — drag-to-bookmarks JS snippet that
 * scrapes the current page's OpenGraph tags and opens a prefilled
 * form on dajprezent.pl. Server-side we *trust the bookmarklet
 * payload* (it runs in the user's browser) and validate it like any
 * other form submission. Heavy fetching/parsing of remote pages
 * happens nowhere — keeps us out of "scrape Allegro" territory.
 */
final class BookmarkletController extends Controller
{
    public function __construct(private readonly CurrentTenant $current) {}

    /**
     * Landing page with the bookmarklet "drag me to your bookmarks" button.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        assert($user !== null);

        return view('owner.bookmarklet.show', [
            'tenants' => $user->tenants()->orderBy('name')->get(),
            'bookmarkletJs' => $this->buildSnippet(),
        ]);
    }

    /**
     * GET — landing from the bookmarklet, prefills form + lets the
     * owner pick which list to add to.
     */
    public function import(Request $request): View
    {
        $user = $request->user();
        assert($user !== null);

        $request->validate([
            'url' => ['nullable', 'url', 'max:1024'],
            'title' => ['nullable', 'string', 'max:200'],
            'price' => ['nullable', 'string', 'max:50'],
        ]);

        return view('owner.bookmarklet.import', [
            'tenants' => $user->tenants()->orderBy('name')->get(),
            'url' => $request->query('url'),
            'title' => $request->query('title'),
            'price' => $this->normalizePrice((string) $request->query('price', '')),
        ]);
    }

    /**
     * POST — actually create the gift on the chosen tenant.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'url' => ['nullable', 'url', 'max:1024'],
            'price_pln' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'priority' => ['required', 'integer', 'between:1,3'],
        ]);

        $tenant = Tenant::query()->findOrFail($data['tenant_id']);
        if (! $user->ownsTenant($tenant)) {
            abort(403);
        }

        $this->current->set($tenant);

        Gift::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'url' => $data['url'] ?? null,
            'price_pln_gr' => isset($data['price_pln']) ? (int) round((float) $data['price_pln'] * 100) : null,
            'priority' => (int) $data['priority'],
            'status' => Gift::STATUS_AVAILABLE,
            'position' => Gift::query()->max('position') + 1,
        ]);

        return redirect()
            ->route('owner.gifts.index', $tenant)
            ->with('status', 'Dodano prezent z bookmarkletu.');
    }

    /**
     * Minified bookmarklet body. Reads OG meta, falls back to <title>,
     * tries common price selectors, then opens a popup on this domain.
     */
    private function buildSnippet(): string
    {
        $base = config('app.url');

        // Single-line javascript: URI. No template strings (some sites
        // sanitize) — concat with +.
        return 'javascript:(function(){'
            .'var m=function(p){var e=document.querySelector("meta[property=\""+p+"\"],meta[name=\""+p+"\"]");return e?e.content:""};'
            .'var price=m("product:price:amount")||m("og:price:amount")||m("twitter:data1")||"";'
            .'var u=encodeURIComponent(location.href);'
            .'var t=encodeURIComponent(m("og:title")||document.title||"");'
            .'var p=encodeURIComponent(price);'
            .'window.open("'.rtrim((string) $base, '/').'/panel/bookmarklet/import?url="+u+"&title="+t+"&price="+p,"dajprezent","width=560,height=720");'
            .'})();';
    }

    /**
     * Cleans up things like "199,99 zł" or "$19.99" to a numeric form.
     */
    private function normalizePrice(string $price): ?string
    {
        $price = preg_replace('/[^0-9,\.]/', '', $price) ?? '';
        if ($price === '') {
            return null;
        }
        // Convert comma decimals to dots; ignore thousand separators if any.
        $price = str_replace(' ', '', $price);
        if (substr_count($price, ',') === 1 && substr_count($price, '.') === 0) {
            $price = str_replace(',', '.', $price);
        } else {
            $price = str_replace(',', '', $price);
        }

        return is_numeric($price) ? $price : null;
    }
}
