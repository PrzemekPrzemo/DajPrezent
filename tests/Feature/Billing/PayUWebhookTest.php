<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    Config::set('services.payu.md5_key', 'webhook-secret');

    $this->package = Package::factory()->create(['valid_days' => 270]);
    $this->tenant = Tenant::factory()->create(['is_public' => false, 'expires_at' => null]);
    $this->subscription = Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'package_id' => $this->package->id,
        'status' => 'pending',
        'amount_pln_gr' => 6900,
    ]);
});

function payuPost($subId, string $status = 'COMPLETED'): array
{
    return [
        'order' => [
            'orderId' => 'PAYU-ORD-'.$subId,
            'extOrderId' => 'dajprezent-sub-'.$subId,
            'status' => $status,
        ],
    ];
}

function signedHeaders(string $body, string $key = 'webhook-secret'): array
{
    $sig = md5($body.$key);

    return [
        // Laravel test framework requires HTTP_* server keys for headers.
        'HTTP_OPENPAYU_SIGNATURE' => "sender=checkout;signature={$sig};algorithm=MD5",
        'CONTENT_TYPE' => 'application/json',
    ];
}

it('activates subscription and publishes tenant on COMPLETED notification', function (): void {
    $body = json_encode(payuPost($this->subscription->id), JSON_THROW_ON_ERROR);

    $this->call('POST', '/webhooks/payu', server: signedHeaders($body), content: $body)
        ->assertOk();

    $this->subscription->refresh();
    $this->tenant->refresh();

    expect($this->subscription->status)->toBe('active')
        ->and($this->subscription->paid_at)->not->toBeNull()
        ->and($this->subscription->expires_at)->not->toBeNull()
        ->and($this->subscription->payu_order_id)->toBe('PAYU-ORD-'.$this->subscription->id)
        ->and($this->tenant->is_public)->toBeTrue()
        ->and($this->tenant->expires_at)->not->toBeNull();
});

it('is idempotent — second COMPLETED is a no-op', function (): void {
    $body = json_encode(payuPost($this->subscription->id), JSON_THROW_ON_ERROR);
    $headers = signedHeaders($body);

    $this->call('POST', '/webhooks/payu', server: $headers, content: $body)->assertOk();
    $firstPaidAt = $this->subscription->fresh()->paid_at;

    $this->call('POST', '/webhooks/payu', server: $headers, content: $body)->assertOk();

    expect($this->subscription->fresh()->paid_at->equalTo($firstPaidAt))->toBeTrue()
        ->and($this->subscription->fresh()->status)->toBe('active');
});

it('rejects an unsigned or wrongly-signed request', function (): void {
    $body = json_encode(payuPost($this->subscription->id), JSON_THROW_ON_ERROR);

    $this->call('POST', '/webhooks/payu', server: ['CONTENT_TYPE' => 'application/json'], content: $body)
        ->assertStatus(401);

    $this->call('POST', '/webhooks/payu', server: [
        'HTTP_OPENPAYU_SIGNATURE' => 'signature=deadbeef;algorithm=MD5',
        'CONTENT_TYPE' => 'application/json',
    ], content: $body)
        ->assertStatus(401);

    expect($this->subscription->fresh()->status)->toBe('pending');
});

it('returns 422 for an unknown extOrderId pattern', function (): void {
    $payload = ['order' => ['orderId' => 'X', 'extOrderId' => 'random-string', 'status' => 'COMPLETED']];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    $this->call('POST', '/webhooks/payu', server: signedHeaders($body), content: $body)
        ->assertStatus(422);
});

it('returns 404 when extOrderId points to a missing subscription', function (): void {
    $payload = ['order' => ['orderId' => 'X', 'extOrderId' => 'dajprezent-sub-9999999', 'status' => 'COMPLETED']];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    $this->call('POST', '/webhooks/payu', server: signedHeaders($body), content: $body)
        ->assertStatus(404);
});

it('marks subscription cancelled on CANCELED notification', function (): void {
    $body = json_encode(payuPost($this->subscription->id, 'CANCELED'), JSON_THROW_ON_ERROR);

    $this->call('POST', '/webhooks/payu', server: signedHeaders($body), content: $body)
        ->assertOk();

    expect($this->subscription->fresh()->status)->toBe('cancelled');
});

it('ignores intermediate statuses (WAITING_FOR_CONFIRMATION, PENDING) without state change', function (string $status): void {
    $body = json_encode(payuPost($this->subscription->id, $status), JSON_THROW_ON_ERROR);

    $this->call('POST', '/webhooks/payu', server: signedHeaders($body), content: $body)
        ->assertOk();

    expect($this->subscription->fresh()->status)->toBe('pending');
})->with(['WAITING_FOR_CONFIRMATION', 'PENDING', 'NEW']);
