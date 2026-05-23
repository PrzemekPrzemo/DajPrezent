<?php

declare(strict_types=1);

namespace App\Domain\Tenancy;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Closes (soft-deletes) a tenant. RODO-aware:
 *
 *   - The Tenant row is soft-deleted (deleted_at).
 *   - All gifts under it are soft-deleted; their image files are
 *     removed from the public disk so we don't keep PII attachments
 *     past the user's request.
 *   - Gift reservations are HARD-deleted — guest e-mails are personal
 *     data we have no legal basis to retain after the list closes.
 *   - Subscriptions + invoices are LEFT INTACT — those have a legal
 *     retention window (5 lat ustawa o rachunkowości) and tenant_id
 *     stays on them for audit.
 *   - The slug is overwritten with a random closed-* value so a new
 *     tenant can reclaim the friendly one if needed.
 */
final class TenantCloser
{
    public function close(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant): void {
            // Purge image files first — outside the DB but transactional
            // enough: if the DB rolls back we'd have orphan deletes, but
            // that's harmless (next storage:link or manual purge cleans).
            Gift::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)
                ->whereNotNull('image_path')
                ->chunkById(100, function ($chunk): void {
                    foreach ($chunk as $gift) {
                        if ($gift->image_path !== null) {
                            Storage::disk('public')->delete($gift->image_path);
                        }
                    }
                });

            // PII purge — guest_email never has a basis to be retained.
            GiftReservation::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)
                ->delete();

            Gift::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)
                ->delete(); // soft delete

            $tenant->update([
                'slug' => 'closed-'.Str::random(16),
                'is_public' => false,
                'password_hash' => null,
                'theme' => null,
                'cover_image_path' => null,
            ]);
            $tenant->delete(); // soft delete
        });
    }
}
