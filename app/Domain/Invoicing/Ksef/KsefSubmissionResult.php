<?php

declare(strict_types=1);

namespace App\Domain\Invoicing\Ksef;

use Illuminate\Support\Carbon;

final class KsefSubmissionResult
{
    public function __construct(
        public readonly string $referenceNumber,
        public readonly Carbon $acquiredAt,
        public readonly bool $isStub,
    ) {}
}
