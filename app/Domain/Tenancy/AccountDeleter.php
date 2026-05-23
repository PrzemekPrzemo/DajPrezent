<?php

declare(strict_types=1);

namespace App\Domain\Tenancy;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * RODO art. 17 — prawo do bycia zapomnianym (account erasure).
 *
 *   1. Each tenant the user owns goes through TenantCloser (gifts,
 *      reservations, images cleaned up; tenant soft-deleted).
 *   2. Subscriptions are anonymised — buyer_* fields blanked so the
 *      row no longer carries PII, but the row itself stays because
 *      it backs an Invoice which has a 5-year legal retention window.
 *   3. Invoices keep buyer_name (legal must — Ustawa o rachunkowości)
 *      ALE the link back to the User row is severed by hard-deleting
 *      the user record. Audit trail retains immutable accounting data,
 *      no PII identification of "who paid".
 *   4. User row is hard-deleted.
 */
final class AccountDeleter
{
    public function __construct(private readonly TenantCloser $closer) {}

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $tenants = Tenant::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('owner_user_id', $user->id)
                ->get();

            foreach ($tenants as $tenant) {
                $this->closer->close($tenant);

                Subscription::query()
                    ->withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $tenant->id)
                    ->update([
                        'buyer_name' => '[usunięty użytkownik]',
                        'buyer_company' => null,
                        'buyer_nip' => null,
                        'buyer_street' => null,
                        'buyer_postal_code' => null,
                        'buyer_city' => null,
                    ]);
            }

            $user->delete();
        });
    }
}
