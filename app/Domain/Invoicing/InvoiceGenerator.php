<?php

declare(strict_types=1);

namespace App\Domain\Invoicing;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Invoicing\Models\Invoice;
use App\Domain\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Builds an Invoice row from a paid Subscription.
 *
 * Numbering: FV/YYYY/MM/####, monthly counter, system-wide. We
 * generate inside a transaction with FOR UPDATE so two webhooks
 * landing in the same millisecond don't collide on the same
 * counter. The format itself comes from config('seller.invoice_number_format').
 *
 * VAT: assume standard 23% (services). Price stored on the
 * Subscription is gross (PayU charges gross); we recover net +
 * VAT cents from it without rounding drift by computing net last:
 *   gross = net + vat   →   net = round(gross / 1.23)
 *   vat   = gross - net
 */
final class InvoiceGenerator
{
    public const VAT_RATE = 23;

    public function generate(Subscription $subscription): Invoice
    {
        return DB::transaction(function () use ($subscription): Invoice {
            $package = $subscription->package;
            $tenant = $subscription->tenant;
            $owner = $tenant->owner;

            // Lock concurrent invoice-creation calls by selecting the
            // existing rows we'd need to check. SQLite ignores
            // lockForUpdate but MySQL honours it.
            $now = $subscription->paid_at ?? now();
            $prefix = sprintf('FV/%04d/%02d/', (int) $now->format('Y'), (int) $now->format('m'));

            $latest = Invoice::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('number')
                ->first();

            $counter = 1;
            if ($latest !== null) {
                $last = (int) substr($latest->number, strlen($prefix));
                $counter = $last + 1;
            }

            $number = $prefix.sprintf('%04d', $counter);

            $totalGross = $subscription->amount_pln_gr;
            $net = (int) round($totalGross * 100 / (100 + self::VAT_RATE));
            $vat = $totalGross - $net;

            if ($owner === null) {
                throw new RuntimeException('Subscription tenant has no owner — cannot issue an invoice.');
            }

            // Subscription carries the buyer billing snapshot captured at
            // checkout (B2C name+address, or company+NIP for B2B). For
            // legacy rows where those fields are still null (pre-billing
            // migration), fall back to the owner's account name + email.
            $isB2B = $subscription->isB2B();
            $buyerName = $isB2B
                ? ($subscription->buyer_company ?? $subscription->buyer_name ?? $owner->name)
                : ($subscription->buyer_name ?? $owner->name);

            $address = [
                'email' => $owner->email,
            ];
            if ($subscription->buyer_street !== null) {
                $address['street'] = $subscription->buyer_street;
            }
            if ($subscription->buyer_postal_code !== null && $subscription->buyer_city !== null) {
                $address['city_line'] = $subscription->buyer_postal_code.' '.$subscription->buyer_city;
            }
            if ($subscription->buyer_country !== '') {
                $address['country'] = $subscription->buyer_country;
            }

            return Invoice::create([
                'tenant_id' => $tenant->id,
                'number' => $number,
                'buyer_name' => $buyerName,
                'buyer_nip' => $isB2B ? $subscription->buyer_nip : null,
                'buyer_address' => $address,
                'items' => [[
                    'name' => 'DajPrezent.pl — '.$package->name,
                    'qty' => 1,
                    'unit_net_gr' => $net,
                    'vat_rate' => self::VAT_RATE,
                    'unit_gross_gr' => $totalGross,
                ]],
                'total_net_gr' => $net,
                'total_vat_gr' => $vat,
                'total_gross_gr' => $totalGross,
                'status' => 'queued',
            ]);
        });
    }
}
