<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\SubscriptionActivator;
use App\Domain\Invoicing\InvoiceGenerator;
use App\Domain\Invoicing\Ksef\KsefClient;
use App\Domain\Invoicing\Models\Invoice;
use App\Domain\Tenancy\Models\Tenant;
use App\Jobs\IssueInvoiceJob;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $owner->id]);
    $package = Package::factory()->create(['price_pln_gr' => 9900, 'valid_days' => 270]);
    $this->subscription = Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $package->id,
        'status' => 'pending',
        'amount_pln_gr' => 9900,
    ]);
});

it('dispatches IssueInvoiceJob after activating a paid subscription', function (): void {
    Queue::fake();

    app(SubscriptionActivator::class)->activate($this->subscription);

    Queue::assertPushed(IssueInvoiceJob::class, function (IssueInvoiceJob $job): bool {
        return $job->subscription->id === $this->subscription->id;
    });
});

it('synchronously generates an invoice with a STUB-KSEF reference', function (): void {
    // Run the job inline.
    (new IssueInvoiceJob($this->subscription))->handle(
        app(InvoiceGenerator::class),
        app(KsefClient::class),
    );

    $invoice = Invoice::query()->where('tenant_id', $this->subscription->tenant_id)->firstOrFail();

    expect($invoice->total_gross_gr)->toBe(9900)
        ->and($invoice->status)->toBe('sent')
        ->and($invoice->ksef_reference_number)->toStartWith('STUB-KSEF-TEST-')
        ->and($invoice->ksef_acquisition_at)->not->toBeNull()
        ->and($this->subscription->fresh()->invoice_id)->toBe($invoice->id);
});

it('does not double-issue if the job re-runs after the subscription already has an invoice', function (): void {
    (new IssueInvoiceJob($this->subscription))->handle(
        app(InvoiceGenerator::class),
        app(KsefClient::class),
    );

    expect(Invoice::query()->count())->toBe(1);

    (new IssueInvoiceJob($this->subscription->fresh()))->handle(
        app(InvoiceGenerator::class),
        app(KsefClient::class),
    );

    expect(Invoice::query()->count())->toBe(1);
});

it('does not dispatch an invoice for a free plan activation', function (): void {
    Bus::fake();

    $freePackage = Package::factory()->create(['price_pln_gr' => 0]);
    $freeSub = Subscription::factory()->create([
        'tenant_id' => $this->subscription->tenant_id,
        'package_id' => $freePackage->id,
        'amount_pln_gr' => 0,
        'status' => 'pending',
    ]);

    app(SubscriptionActivator::class)->activate($freeSub);

    Bus::assertNotDispatched(IssueInvoiceJob::class);
});
