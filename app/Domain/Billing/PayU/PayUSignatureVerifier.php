<?php

declare(strict_types=1);

namespace App\Domain\Billing\PayU;

/**
 * PayU sends notifications with an `OpenPayu-Signature` header like:
 *
 *   sender=checkout;signature=<md5>;algorithm=MD5;content=DOCUMENT
 *
 * The signature is md5($rawBody . $secondKey). We verify in constant
 * time and refuse anything but MD5 — PayU's docs allow MD5 only for
 * this header.
 *
 * Reference: https://developers.payu.com/restapi/#notify
 */
final class PayUSignatureVerifier
{
    public function __construct(private readonly string $md5Key) {}

    public function verify(string $rawBody, string $signatureHeader): bool
    {
        if ($this->md5Key === '') {
            return false;
        }

        $parts = [];
        foreach (explode(';', $signatureHeader) as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }
            [$k, $v] = array_pad(explode('=', $segment, 2), 2, '');
            $parts[strtolower($k)] = $v;
        }

        if (strtoupper($parts['algorithm'] ?? '') !== 'MD5') {
            return false;
        }

        $signature = $parts['signature'] ?? '';
        if ($signature === '') {
            return false;
        }

        $expected = md5($rawBody.$this->md5Key);

        return hash_equals($expected, strtolower($signature));
    }
}
