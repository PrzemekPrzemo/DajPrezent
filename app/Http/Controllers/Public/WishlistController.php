<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class WishlistController extends Controller
{
    public function show(Request $request): View
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        if ($tenant->isPasswordProtected() && ! $this->isUnlocked($request, $tenant)) {
            throw new HttpException(423, 'Lista chroniona hasłem.');
        }

        $gifts = Gift::query()
            ->orderBy('position')
            ->orderBy('priority')
            ->orderByDesc('id')
            ->get();

        return view('public.wishlist.show', [
            'tenant' => $tenant,
            'gifts' => $gifts,
        ]);
    }

    private function isUnlocked(Request $request, Tenant $tenant): bool
    {
        return $request->session()->get("tenant.unlocked.{$tenant->id}") === true;
    }
}
