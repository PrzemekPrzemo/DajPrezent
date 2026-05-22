<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Config;

/**
 * Validates that a chosen slug is not on the reserved blacklist.
 *
 * The list lives in config/packages.php under `reserved_slugs` so it
 * stays close to the routing & pricing definitions and is easy to extend.
 */
final class AllowedSlug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('Slug jest wymagany.');

            return;
        }

        $trimmed = trim($value);

        if (! preg_match('/^[a-z0-9][a-z0-9-]{0,38}[a-z0-9]$/', $trimmed)) {
            $fail('Slug może zawierać tylko małe litery, cyfry i myślniki (2–40 znaków).');

            return;
        }

        $reserved = array_map('strtolower', Config::array('packages.reserved_slugs'));

        if (in_array($trimmed, $reserved, true)) {
            $fail('Wybrany adres jest zarezerwowany dla systemu. Wybierz inny.');
        }
    }
}
