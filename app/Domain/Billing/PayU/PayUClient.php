<?php

declare(strict_types=1);

namespace App\Domain\Billing\PayU;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Thin client over the PayU REST API.
 *
 * Auth via OAuth2 client_credentials. Access tokens are cached in Redis
 * with a small safety margin against the expires_in we get back. Order
 * creation always uses a caller-supplied `extOrderId` (our subscription
 * id, namespaced) so retries and webhooks stay idempotent.
 *
 * Docs: https://developers.payu.com/en/restapi.html
 */
final class PayUClient
{
    private const TOKEN_CACHE_KEY = 'payu.access_token';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $baseUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $posId,
    ) {}

    public function createOrder(PayUOrderRequest $request): PayUOrderResponse
    {
        $token = $this->getAccessToken();

        $response = $this->http
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->withOptions(['allow_redirects' => false]) // PayU returns 302 with Location on success.
            ->post(rtrim($this->baseUrl, '/').'/api/v2_1/orders', [
                'extOrderId' => $request->extOrderId,
                'notifyUrl' => $request->notifyUrl,
                'continueUrl' => $request->continueUrl,
                'customerIp' => $request->customerIp,
                'merchantPosId' => $this->posId,
                'description' => $request->description,
                'currencyCode' => 'PLN',
                'totalAmount' => (string) $request->totalAmountGr,
                'buyer' => [
                    'email' => $request->buyerEmail,
                    'firstName' => $request->buyerName,
                    'language' => 'pl',
                ],
                'products' => array_map(static fn (array $p): array => [
                    'name' => $p['name'],
                    'unitPrice' => (string) $p['unitPriceGr'],
                    'quantity' => '1',
                ], $request->products),
            ]);

        // PayU semantics: a 302 with `redirectUri` in the JSON body means
        // success. The status code 200 also indicates success; both come
        // back with a body. Anything else we treat as a failure.
        if (! in_array($response->status(), [200, 302], true)) {
            throw new RuntimeException('PayU create-order failed: HTTP '.$response->status().' '.$response->body());
        }

        $data = $response->json();
        $status = $data['status']['statusCode'] ?? null;
        if ($status !== 'SUCCESS') {
            throw new RuntimeException('PayU create-order rejected: '.($data['status']['code'] ?? '?').' '.($data['status']['codeLiteral'] ?? ''));
        }

        return new PayUOrderResponse(
            orderId: (string) $data['orderId'],
            redirectUri: (string) $data['redirectUri'],
            extOrderId: (string) ($data['extOrderId'] ?? $request->extOrderId),
        );
    }

    public function getAccessToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = $this->http
            ->asForm()
            ->acceptJson()
            ->post(rtrim($this->baseUrl, '/').'/pl/standard/user/oauth/authorize', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('PayU OAuth failed: HTTP '.$response->status());
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;
        $expiresIn = (int) ($data['expires_in'] ?? 0);

        if (! is_string($token) || $token === '' || $expiresIn <= 0) {
            throw new RuntimeException('PayU OAuth: malformed response.');
        }

        // Cache for slightly less than expires_in so we never hand out a
        // freshly-expired token under clock skew.
        Cache::put(self::TOKEN_CACHE_KEY, $token, max(60, $expiresIn - 60));

        return $token;
    }
}
