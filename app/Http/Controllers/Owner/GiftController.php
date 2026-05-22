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

final class GiftController extends Controller
{
    public function __construct(private readonly CurrentTenant $current) {}

    public function index(Request $request, Tenant $tenant): View
    {
        $this->authorizeTenant($request, $tenant);
        $this->current->set($tenant);

        $gifts = Gift::query()
            ->orderBy('position')
            ->orderByDesc('id')
            ->get();

        return view('owner.gifts.index', [
            'tenant' => $tenant,
            'gifts' => $gifts,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorizeTenant($request, $tenant);
        $this->current->set($tenant);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'url' => ['nullable', 'url', 'max:1024'],
            'price_pln' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'priority' => ['required', 'integer', 'between:1,3'],
        ]);

        Gift::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'url' => $data['url'] ?? null,
            'price_pln_gr' => isset($data['price_pln']) ? (int) round($data['price_pln'] * 100) : null,
            'priority' => (int) $data['priority'],
            'status' => Gift::STATUS_AVAILABLE,
            'position' => Gift::query()->max('position') + 1,
        ]);

        return redirect()
            ->route('owner.gifts.index', $tenant)
            ->with('status', 'Prezent dodany do listy.');
    }

    public function update(Request $request, Tenant $tenant, int $gift): RedirectResponse
    {
        $this->authorizeTenant($request, $tenant);
        $this->current->set($tenant);

        $giftModel = Gift::query()->findOrFail($gift);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'url' => ['nullable', 'url', 'max:1024'],
            'price_pln' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'priority' => ['required', 'integer', 'between:1,3'],
        ]);

        $giftModel->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'url' => $data['url'] ?? null,
            'price_pln_gr' => isset($data['price_pln']) ? (int) round($data['price_pln'] * 100) : null,
            'priority' => (int) $data['priority'],
        ]);

        return back()->with('status', 'Zapisano zmiany.');
    }

    public function destroy(Request $request, Tenant $tenant, int $gift): RedirectResponse
    {
        $this->authorizeTenant($request, $tenant);
        $this->current->set($tenant);

        Gift::query()->findOrFail($gift)->delete();

        return back()->with('status', 'Prezent usunięty.');
    }

    public function markReceived(Request $request, Tenant $tenant, int $gift): RedirectResponse
    {
        $this->authorizeTenant($request, $tenant);
        $this->current->set($tenant);

        $giftModel = Gift::query()->findOrFail($gift);
        $giftModel->update(['status' => Gift::STATUS_RECEIVED]);

        return back()->with('status', 'Oznaczono prezent jako otrzymany.');
    }

    private function authorizeTenant(Request $request, Tenant $tenant): void
    {
        $user = $request->user();
        if ($user === null || ! $user->ownsTenant($tenant)) {
            abort(403);
        }
    }
}
