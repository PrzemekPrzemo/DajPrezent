<?php

declare(strict_types=1);

namespace App\Domain\Billing\Checkout;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;

final class CheckoutResult
{
    public function __construct(
        public readonly Tenant $tenant,
        public readonly Subscription $subscription,
        public readonly ?string $redirectUri,
    ) {}
}
