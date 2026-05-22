<?php

declare(strict_types=1);

namespace App\Domain\Billing\PayU;

final class PayUOrderResponse
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $redirectUri,
        public readonly string $extOrderId,
    ) {}
}
