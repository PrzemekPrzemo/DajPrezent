<?php

declare(strict_types=1);

namespace App\Domain\Billing\Checkout;

final class CheckoutOrderData
{
    public function __construct(
        public readonly string $slug,
        public readonly string $tenantName,
        public readonly string $locale,
        public readonly string $customerIp,
    ) {}
}
