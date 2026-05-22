<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\PayU\PayUSignatureVerifier;
use App\Domain\Billing\SubscriptionActivator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Receives PayU IPN notifications. Critical properties:
 *
 *   - Signature verified against PAYU_MD5_KEY in constant time.
 *   - Lookup by `extOrderId` (== `subscription_id`) gives us the
 *     local subscription record straight from the payload.
 *   - State transitions are delegated to SubscriptionActivator,
 *     which uses lockForUpdate + status guards so duplicate calls
 *     are no-ops.
 *
 * PayU expects HTTP 2xx (any body) to consider the notification
 * delivered; 4xx/5xx will be retried with exponential backoff.
 */
final class PayUWebhookController extends Controller
{
    public function __construct(
        private readonly PayUSignatureVerifier $verifier,
        private readonly SubscriptionActivator $activator,
    ) {}

    public function __invoke(Request $request): Response
    {
        $signatureHeader = (string) $request->header('OpenPayu-Signature', '');
        $body = (string) $request->getContent();

        if (! $this->verifier->verify($body, $signatureHeader)) {
            Log::warning('payu.webhook.invalid_signature', [
                'ip' => $request->ip(),
                'header_present' => $signatureHeader !== '',
            ]);

            return response('invalid signature', 401);
        }

        /** @var array{order?: array{orderId?: string, extOrderId?: string, status?: string}} $payload */
        $payload = (array) $request->json()->all();
        $order = (array) ($payload['order'] ?? []);
        $extOrderId = (string) ($order['extOrderId'] ?? '');
        $payuOrderId = (string) ($order['orderId'] ?? '');
        $status = strtoupper((string) ($order['status'] ?? ''));

        if ($extOrderId === '' || $status === '') {
            return response('missing extOrderId or status', 422);
        }

        $subscriptionId = $this->parseSubscriptionId($extOrderId);
        if ($subscriptionId === null) {
            Log::warning('payu.webhook.unknown_ext_order', ['extOrderId' => $extOrderId]);

            return response('unknown extOrderId', 422);
        }

        $sub = Subscription::query()->find($subscriptionId);
        if ($sub === null) {
            Log::warning('payu.webhook.subscription_missing', ['subscription_id' => $subscriptionId]);

            return response('subscription not found', 404);
        }

        // Capture the PayU order id the first time we see it so support
        // can correlate refunds later. Don't overwrite if already set.
        if ($sub->payu_order_id === null && $payuOrderId !== '') {
            $sub->update(['payu_order_id' => $payuOrderId]);
        }

        $action = match ($status) {
            'COMPLETED' => fn () => $this->activator->activate($sub),
            'CANCELED', 'CANCELLED', 'REJECTED' => fn () => $this->activator->markCancelled($sub),
            'WAITING_FOR_CONFIRMATION', 'PENDING', 'NEW' => static fn (): bool => false,
            default => null,
        };

        if ($action === null) {
            Log::info('payu.webhook.ignored_status', ['status' => $status]);

            return response('ignored', 200);
        }

        $action();

        return response('ok', 200);
    }

    /**
     * extOrderId convention: "dajprezent-sub-<id>". Any other shape is
     * either a payload from a different system or a misconfiguration.
     */
    private function parseSubscriptionId(string $extOrderId): ?int
    {
        if (! preg_match('/^dajprezent-sub-(\d+)$/', $extOrderId, $m)) {
            return null;
        }

        return (int) $m[1];
    }
}
