<?php

declare(strict_types=1);

namespace App\Domain\Billing\Checkout;

final class CheckoutOrderData
{
    /**
     * @param  ?string  $buyerCompany  null = B2C (osoba fizyczna),
     *                                 non-null = B2B (firma)
     * @param  ?string  $buyerNip  Polish NIP (10 digits), required
     *                             when buyerCompany is set
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $tenantName,
        public readonly string $locale,
        public readonly string $customerIp,
        public readonly string $buyerName,
        public readonly ?string $buyerCompany,
        public readonly ?string $buyerNip,
        public readonly string $buyerStreet,
        public readonly string $buyerPostalCode,
        public readonly string $buyerCity,
        public readonly string $buyerCountry = 'PL',
    ) {}
}
