<?php

declare(strict_types=1);

use App\Domain\Billing\PayU\PayUSignatureVerifier;

it('accepts a correctly-signed body', function (): void {
    $verifier = new PayUSignatureVerifier('top-secret-key');
    $body = '{"order":{"orderId":"X1","status":"COMPLETED","extOrderId":"dajprezent-sub-1"}}';
    $sig = md5($body.'top-secret-key');

    $header = "sender=checkout;signature={$sig};algorithm=MD5;content=DOCUMENT";

    expect($verifier->verify($body, $header))->toBeTrue();
});

it('rejects a tampered body', function (): void {
    $verifier = new PayUSignatureVerifier('top-secret-key');
    $original = '{"a":1}';
    $sig = md5($original.'top-secret-key');
    $tampered = '{"a":2}';

    $header = "signature={$sig};algorithm=MD5";

    expect($verifier->verify($tampered, $header))->toBeFalse();
});

it('rejects non-MD5 algorithms', function (): void {
    $verifier = new PayUSignatureVerifier('k');
    $sig = md5('xk');
    $header = "signature={$sig};algorithm=SHA256";

    expect($verifier->verify('x', $header))->toBeFalse();
});

it('rejects empty headers', function (): void {
    $verifier = new PayUSignatureVerifier('k');
    expect($verifier->verify('body', ''))->toBeFalse();
});

it('refuses to verify when no MD5 key configured', function (): void {
    $verifier = new PayUSignatureVerifier('');
    expect($verifier->verify('body', 'signature=anything;algorithm=MD5'))->toBeFalse();
});
