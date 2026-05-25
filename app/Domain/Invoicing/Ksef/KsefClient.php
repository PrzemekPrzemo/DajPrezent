<?php

declare(strict_types=1);

namespace App\Domain\Invoicing\Ksef;

use App\Domain\Invoicing\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * KSeF stub client.
 *
 * Real integration with KSeF requires:
 *   - signed/unsigned authorization tokens for the seller NIP,
 *   - FA(2) schema XML emission with checksums,
 *   - polling for `acquisition_number` after submission.
 *
 * Two auth modes are supported in configuration:
 *   - **token** — single string token from KSeF web account
 *     (simplest, no PFX needed).
 *   - **certificate** — `.pfx` (PKCS#12) signed by KIR/Sigillum
 *     uploaded via the admin panel and stored under the private
 *     disk at `ksef/<filename>.pfx`. The certificate passphrase is
 *     encrypted at rest by SettingsRepository.
 *
 * `isConfigured()` returns true when either mode has the required
 * inputs and NIP is set. `submit()` in non-prod env (or when not
 * configured) synthesises a stub KSeF reference so the rest of the
 * billing pipeline (invoice generation, mail dispatch) is testable
 * end-to-end without real MF connectivity.
 */
final class KsefClient
{
    public function __construct(
        private readonly string $env,    // 'test' | 'demo' | 'prod'
        private readonly string $nip,
        private readonly ?string $token = null,
        private readonly ?string $certPath = null,
        private readonly ?string $certPassword = null,
        // Pair for the X.509 path — when cert is .crt/.cer/.pem, the
        // private key arrives as a separate file. PKCS#12 (.pfx) bundles
        // both inside one file so these stay null in that mode.
        // $keyPassword is read by the real adapter (not yet shipped) when
        // it opens an encrypted PEM key — phpstan-ignore unused warning.
        private readonly ?string $keyPath = null,
        private readonly ?string $keyPassword = null, // @phpstan-ignore-line property.unusedType
    ) {}

    public function isConfigured(): bool
    {
        if ($this->nip === '') {
            return false;
        }

        $hasToken = $this->token !== null && $this->token !== '';
        $hasPkcs12 = $this->certIsPkcs12() && $this->certPassword !== null && $this->certPassword !== '';
        $hasX509Pair = $this->certIsX509() && $this->keyPath !== null && $this->keyPath !== ''
            && Storage::disk('local')->exists('ksef/'.$this->keyPath);

        return $hasToken || $hasPkcs12 || $hasX509Pair;
    }

    /** For diagnostics in the admin panel — reports which mode is in use. */
    public function authMode(): string
    {
        if ($this->certIsPkcs12()) {
            return 'pkcs12';
        }
        if ($this->certIsX509() && $this->keyPath !== null && $this->keyPath !== '') {
            return 'x509-pair';
        }
        if ($this->token !== null && $this->token !== '') {
            return 'token';
        }

        return 'none';
    }

    private function certIsPkcs12(): bool
    {
        if ($this->certPath === null || $this->certPath === ''
            || ! Storage::disk('local')->exists('ksef/'.$this->certPath)) {
            return false;
        }
        $ext = strtolower(pathinfo($this->certPath, PATHINFO_EXTENSION));

        return in_array($ext, ['pfx', 'p12'], true);
    }

    private function certIsX509(): bool
    {
        if ($this->certPath === null || $this->certPath === ''
            || ! Storage::disk('local')->exists('ksef/'.$this->certPath)) {
            return false;
        }
        $ext = strtolower(pathinfo($this->certPath, PATHINFO_EXTENSION));

        return in_array($ext, ['crt', 'cer', 'pem'], true);
    }

    /**
     * Submits the invoice and returns the KSeF reference number.
     * In stub mode synthesises a deterministic, debuggable number.
     */
    public function submit(Invoice $invoice): KsefSubmissionResult
    {
        if (! $this->isConfigured() || $this->env !== 'prod') {
            $ref = sprintf(
                'STUB-KSEF-%s-%s',
                strtoupper($this->env),
                str_replace('/', '-', $invoice->number),
            );

            Log::info('ksef.submit.stub', [
                'env' => $this->env,
                'mode' => $this->authMode(),
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'reference' => $ref,
            ]);

            return new KsefSubmissionResult(
                referenceNumber: $ref,
                acquiredAt: now(),
                isStub: true,
            );
        }

        // Real KSeF call will live here once we have the production
        // adapter. For now blow up loudly so we never accidentally
        // ship "prod KSeF" without a working client.
        throw new \RuntimeException('Real KSeF integration is not yet implemented — set KSeF env to test/demo until ready.');
    }
}
