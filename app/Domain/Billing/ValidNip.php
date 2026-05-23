<?php

declare(strict_types=1);

namespace App\Domain\Billing;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidNip implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! PolishNip::isValid($value)) {
            $fail('Podany NIP jest nieprawidłowy.');
        }
    }
}
