<?php

declare(strict_types=1);

namespace App\Domain\Tenancy;

use App\Domain\Invoicing\Models\Invoice;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Wishlist\Models\Gift;
use App\Models\User;

/**
 * RODO art. 20 — right to data portability. Returns the user's data
 * as a structured array; caller serialises to JSON for download.
 *
 * Scope: covers the User account + all owned tenants + the gifts on
 * each (titles, prices — public-list data, no PII other than the
 * owner's own). Reservations carry guest e-mails (third-party PII),
 * which we do NOT export — the owner is not the data controller for
 * those rows.
 */
final class TenantDataExporter
{
    /**
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        $tenants = Tenant::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('owner_user_id', $user->id)
            ->withTrashed()
            ->orderBy('id')
            ->get();

        $tenantPayload = $tenants->map(function (Tenant $tenant): array {
            $gifts = Gift::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)
                ->withTrashed()
                ->orderBy('id')
                ->get(['id', 'title', 'description', 'url', 'price_pln_gr', 'priority', 'category', 'status', 'position', 'created_at', 'deleted_at']);

            $invoices = Invoice::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)
                ->orderBy('id')
                ->get(['number', 'buyer_name', 'buyer_nip', 'total_gross_gr', 'total_net_gr', 'total_vat_gr', 'status', 'ksef_reference_number', 'created_at']);

            return [
                'slug' => $tenant->slug,
                'name' => $tenant->name,
                'kind' => $tenant->kind,
                'locale' => $tenant->locale,
                'expires_at' => $tenant->expires_at?->toIso8601String(),
                'is_public' => $tenant->is_public,
                'created_at' => $tenant->created_at?->toIso8601String(),
                'deleted_at' => $tenant->deleted_at?->toIso8601String(),
                'gifts' => $gifts->toArray(),
                'invoices' => $invoices->toArray(),
            ];
        });

        return [
            'exported_at' => now()->toIso8601String(),
            'rodo_notice' => 'Eksport realizujący prawo do przenoszenia danych (art. 20 RODO). Nie zawiera danych osobowych gości, którzy rezerwowali prezenty — Sendormeco Holding sp. z o.o. jest dla tych danych podmiotem przetwarzającym, a właściciel listy NIE jest ich administratorem.',
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => optional($user->email_verified_at)->toIso8601String(),
                'created_at' => optional($user->created_at)->toIso8601String(),
            ],
            'tenants' => $tenantPayload->toArray(),
        ];
    }
}
