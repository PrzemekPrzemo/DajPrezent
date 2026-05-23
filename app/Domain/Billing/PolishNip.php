<?php

declare(strict_types=1);

namespace App\Domain\Billing;

/**
 * Polish NIP (Tax Identification Number) validator.
 *
 * 10 digits, the last one is a checksum computed from the first 9
 * with weights 6,5,7,2,3,4,5,6,7. A checksum of 10 means the NIP is
 * invalid (per the official rules — such a NIP is never issued).
 *
 * Accepts separators (-, spaces) on input — strips them.
 */
final class PolishNip
{
    /** @var list<int> */
    private const WEIGHTS = [6, 5, 7, 2, 3, 4, 5, 6, 7];

    public static function normalize(string $raw): string
    {
        return preg_replace('/[^0-9]/', '', $raw) ?? '';
    }

    public static function isValid(string $raw): bool
    {
        $nip = self::normalize($raw);

        if (strlen($nip) !== 10) {
            return false;
        }

        // Reject obviously bogus values (all same digit).
        if (preg_match('/^(\d)\1{9}$/', $nip)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $nip[$i] * self::WEIGHTS[$i];
        }

        $check = $sum % 11;
        if ($check === 10) {
            return false;
        }

        return $check === (int) $nip[9];
    }
}
