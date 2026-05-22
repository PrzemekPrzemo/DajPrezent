<?php

declare(strict_types=1);

namespace App\Domain\Invoicing\Ksef;

use App\Domain\Invoicing\Models\Invoice;
use Illuminate\Support\Facades\Log;

/**
 * KSeF stub client.
 *
 * Real integration with KSeF requires:
 *   - signed/unsigned authorization tokens for the seller NIP,
 *   - FA(2) schema XML emission with checksums,
 *   - polling for `acquisition_number` after submission.
 *
 * This stub captures the contract (submit(Invoice) → reference
 * number) so calling code, queues and tests can be wired now,
 * and the real adapter swaps in once we have the production
 * token + seller address on file. In `local` / `test` env we
 * just synthesise a fake KSeF reference so flows are
 * end-to-end testable.
 */
final class KsefClient
{
    public function __construct(
        private readonly string $env,    // 'test' | 'demo' | 'prod'
        private readonly string $nip,
        private readonly ?string $token,
    ) {}

    public function isConfigured(): bool
    {
        return $this->nip !== '' && $this->token !== null && $this->token !== '';
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
        throw new \RuntimeException('Real KSeF integration is not yet implemented — set KSEF_ENV=test until ready.');
    }
}
