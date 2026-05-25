<?php

declare(strict_types=1);

namespace App\Domain\Invoicing;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Invoicing\Models\Invoice;
use App\Domain\Settings\SettingsRepository;
use App\Domain\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;
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

    public function __construct(private readonly SettingsRepository $settings) {}

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
            $number = $this->nextNumber($now);

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

    /**
     * Generate the next invoice number for the given moment, honoring
     * the format + reset frequency configured in /admin/settings.
     *
     * Format placeholders:
     *   {YYYY}/{YY}/{MM}/{DD}  — date parts
     *   {N}                    — bare counter (no padding)
     *   {NNNN}/{NN}/etc.       — counter zero-padded to N digits
     *
     * Reset frequency:
     *   monthly  → counter restarts at 1 each month
     *   yearly   → counter restarts at 1 each year
     *   never    → counter never resets (system-wide running total)
     */
    private function nextNumber(Carbon $now): string
    {
        $format = (string) $this->settings->get('invoice.number_format', 'FV/{YYYY}/{MM}/{NNNN}');
        $reset = (string) $this->settings->get('invoice.sequence_reset', 'monthly');
        $startNumber = (int) $this->settings->get('invoice.start_number', 1);

        // Render everything except the {N…} placeholder so we get a
        // stable prefix we can use to find the last counter in DB.
        $prefixTpl = preg_replace('/\{N+\}/', '___COUNTER___', $format) ?? $format;
        $prefixTpl = $this->expandDate($prefixTpl, $now);
        [$prefix, $suffix] = explode('___COUNTER___', $prefixTpl, 2) + [1 => ''];

        // Determine the search window in DB based on the reset policy.
        $likeFilter = match ($reset) {
            'yearly' => $this->expandDate(preg_replace('/\{MM\}|\{DD\}/', '%', $prefixTpl) ?? $prefixTpl, $now),
            'never' => '%',
            default => $prefix.'%', // monthly = exactly this prefix
        };

        $latest = Invoice::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('number', 'like', $likeFilter)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $counter = $startNumber;
        if ($latest !== null) {
            // Pull the trailing digits — works for any format placement.
            if (preg_match('/(\d+)\D*$/', $latest->number, $m) === 1) {
                $counter = ((int) $m[1]) + 1;
            }
        }

        return $this->renderCounter($format, $counter, $now);
    }

    private function expandDate(string $tpl, Carbon $now): string
    {
        return strtr($tpl, [
            '{YYYY}' => $now->format('Y'),
            '{YY}' => $now->format('y'),
            '{MM}' => $now->format('m'),
            '{DD}' => $now->format('d'),
        ]);
    }

    private function renderCounter(string $format, int $counter, Carbon $now): string
    {
        $rendered = $this->expandDate($format, $now);

        return preg_replace_callback(
            '/\{(N+)\}/',
            fn (array $m): string => str_pad((string) $counter, strlen($m[1]), '0', STR_PAD_LEFT),
            $rendered
        ) ?? $rendered;
    }
}
