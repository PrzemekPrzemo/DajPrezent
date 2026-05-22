<?php

declare(strict_types=1);

namespace App\Domain\Billing\PayU;

/**
 * @phpstan-type Product array{name: string, unitPriceGr: int}
 */
final class PayUOrderRequest
{
    /**
     * @param  list<Product>  $products
     */
    public function __construct(
        public readonly string $extOrderId,
        public readonly int $totalAmountGr,
        public readonly string $description,
        public readonly string $buyerEmail,
        public readonly ?string $buyerName,
        public readonly string $customerIp,
        public readonly string $notifyUrl,
        public readonly string $continueUrl,
        public readonly array $products,
    ) {}
}
