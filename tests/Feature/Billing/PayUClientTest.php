<?php

declare(strict_types=1);

use App\Domain\Billing\PayU\PayUClient;
use App\Domain\Billing\PayU\PayUOrderRequest;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();
});

function makeClient(): PayUClient
{
    return new PayUClient(
        http: app(HttpFactory::class),
        baseUrl: 'https://secure.snd.payu.com',
        clientId: 'cid',
        clientSecret: 'csec',
        posId: '300746',
    );
}

it('fetches and caches an access token', function (): void {
    Http::fake([
        'secure.snd.payu.com/pl/standard/user/oauth/authorize' => Http::response([
            'access_token' => 'tok-abc',
            'expires_in' => 3600,
        ], 200),
    ]);

    $client = makeClient();

    expect($client->getAccessToken())->toBe('tok-abc');

    // Second call returns from cache and does NOT hit the API.
    expect($client->getAccessToken())->toBe('tok-abc');
    Http::assertSentCount(1);
});

it('throws when OAuth fails', function (): void {
    Http::fake([
        'secure.snd.payu.com/*' => Http::response(['error' => 'nope'], 401),
    ]);

    makeClient()->getAccessToken();
})->throws(RuntimeException::class, 'PayU OAuth failed');

it('creates an order and returns the redirect URI', function (): void {
    Http::fake([
        'secure.snd.payu.com/pl/standard/user/oauth/authorize' => Http::response([
            'access_token' => 'tok',
            'expires_in' => 3600,
        ], 200),
        'secure.snd.payu.com/api/v2_1/orders' => Http::response([
            'status' => ['statusCode' => 'SUCCESS'],
            'redirectUri' => 'https://secure.snd.payu.com/?orderId=ORDER123',
            'orderId' => 'ORDER123',
            'extOrderId' => 'dajprezent-sub-42',
        ], 302),
    ]);

    $request = new PayUOrderRequest(
        extOrderId: 'dajprezent-sub-42',
        totalAmountGr: 6900,
        description: 'Pakiet Plus',
        buyerEmail: 'klient@example.com',
        buyerName: 'Anna',
        customerIp: '127.0.0.1',
        notifyUrl: 'https://dajprezent.pl/webhooks/payu',
        continueUrl: 'https://dajprezent.pl/buy/return',
        products: [['name' => 'Pakiet Plus', 'unitPriceGr' => 6900]],
    );

    $response = makeClient()->createOrder($request);

    expect($response->orderId)->toBe('ORDER123')
        ->and($response->redirectUri)->toContain('orderId=ORDER123')
        ->and($response->extOrderId)->toBe('dajprezent-sub-42');

    Http::assertSent(function ($req): bool {
        if (str_contains((string) $req->url(), 'orders')) {
            $body = $req->data();

            return $body['extOrderId'] === 'dajprezent-sub-42'
                && $body['totalAmount'] === '6900'
                && $body['merchantPosId'] === '300746'
                && $body['products'][0]['unitPrice'] === '6900';
        }

        return true;
    });
});

it('throws when PayU rejects the order', function (): void {
    Http::fake([
        'secure.snd.payu.com/pl/standard/user/oauth/authorize' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
        'secure.snd.payu.com/api/v2_1/orders' => Http::response([
            'status' => ['statusCode' => 'BUSINESS_ERROR', 'codeLiteral' => 'INSUFFICIENT_FUNDS'],
        ], 200),
    ]);

    makeClient()->createOrder(new PayUOrderRequest(
        extOrderId: 'x',
        totalAmountGr: 1,
        description: 'x',
        buyerEmail: 'a@b.com',
        buyerName: null,
        customerIp: '1.1.1.1',
        notifyUrl: 'https://x/y',
        continueUrl: 'https://x/z',
        products: [['name' => 'x', 'unitPriceGr' => 1]],
    ));
})->throws(RuntimeException::class, 'PayU create-order rejected');
