<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Invoicing\InvoiceGenerator;
use App\Domain\Invoicing\Ksef\KsefClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued: generate the Invoice row, submit to KSeF, persist the
 * acquisition number. Retried up to 5x with backoff in case KSeF
 * is unreachable.
 */
final class IssueInvoiceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @return list<int> seconds to wait between retries */
    public function backoff(): array
    {
        return [30, 120, 600, 1800, 3600];
    }

    public function __construct(public Subscription $subscription) {}

    public function handle(InvoiceGenerator $generator, KsefClient $ksef): void
    {
        // Lock against double-issue: if this subscription already has an
        // invoice, treat the job as no-op.
        if ($this->subscription->invoice_id !== null) {
            return;
        }

        $invoice = $generator->generate($this->subscription->fresh());
        $this->subscription->update(['invoice_id' => $invoice->id]);

        $result = $ksef->submit($invoice);

        $invoice->update([
            'status' => $result->isStub ? 'sent' : 'accepted',
            'ksef_reference_number' => $result->referenceNumber,
            'ksef_acquisition_at' => $result->acquiredAt,
        ]);
    }
}
