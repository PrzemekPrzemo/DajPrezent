<?php

declare(strict_types=1);

namespace App\Domain\Billing\Checkout;

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\PayU\PayUClient;
use App\Domain\Billing\PayU\PayUOrderRequest;
use App\Domain\Billing\SubscriptionActivator;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates everything that happens between "user clicks Buy"
 * and "user lands on PayU's checkout (or the success page for
 * a free plan)".
 *
 *  1. validates inputs upstream (controller does request validation)
 *  2. creates a pending Tenant + Subscription in one tx so we never
 *     have a half-created tenant if PayU is down
 *  3. for paid plans, calls PayUClient::createOrder and returns the
 *     redirect URL
 *  4. for free plans (price_pln_gr = 0), activates immediately and
 *     returns null (no redirect needed — caller routes to dashboard)
 */
final class CheckoutService
{
    public function __construct(
        private readonly PayUClient $payu,
        private readonly SubscriptionActivator $activator,
    ) {}

    public function start(User $buyer, Package $package, CheckoutOrderData $data): CheckoutResult
    {
        [$tenant, $subscription] = DB::transaction(function () use ($buyer, $package, $data): array {
            $tenant = Tenant::create([
                'owner_user_id' => $buyer->id,
                'slug' => $data->slug,
                'name' => $data->tenantName,
                'kind' => str_starts_with($package->code, 'wedding_') ? $package->code : 'wishlist',
                'locale' => $data->locale,
                'is_public' => false,
                'expires_at' => null,
            ]);

            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'package_id' => $package->id,
                'status' => 'pending',
                'amount_pln_gr' => $package->price_pln_gr,
                'buyer_name' => $data->buyerName,
                'buyer_company' => $data->buyerCompany,
                'buyer_nip' => $data->buyerNip,
                'buyer_street' => $data->buyerStreet,
                'buyer_postal_code' => $data->buyerPostalCode,
                'buyer_city' => $data->buyerCity,
                'buyer_country' => $data->buyerCountry,
            ]);

            return [$tenant, $subscription];
        });

        if ($package->price_pln_gr === 0) {
            $this->activator->activate($subscription);

            return new CheckoutResult($tenant, $subscription, redirectUri: null);
        }

        $request = new PayUOrderRequest(
            extOrderId: 'dajprezent-sub-'.$subscription->id,
            totalAmountGr: $package->price_pln_gr,
            description: 'DajPrezent.pl — '.$package->name,
            buyerEmail: $buyer->email,
            buyerName: $buyer->name,
            customerIp: $data->customerIp,
            notifyUrl: url(route('webhooks.payu', absolute: false)),
            continueUrl: url(route('public.checkout.return', absolute: false)),
            products: [[
                'name' => 'Pakiet '.$package->name,
                'unitPriceGr' => $package->price_pln_gr,
            ]],
        );

        $response = $this->payu->createOrder($request);

        $subscription->update(['payu_order_id' => $response->orderId]);

        return new CheckoutResult($tenant, $subscription, redirectUri: $response->redirectUri);
    }
}
