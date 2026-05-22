<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Billing\PayU\PayUClient;
use App\Domain\Billing\PayU\PayUSignatureVerifier;
use App\Domain\Invoicing\Ksef\KsefClient;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

final class PayUServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PayUClient::class, function ($app): PayUClient {
            $cfg = (array) $app['config']['services.payu'];

            return new PayUClient(
                http: $app->make(HttpFactory::class),
                baseUrl: (string) ($cfg['base_url'] ?? 'https://secure.snd.payu.com'),
                clientId: (string) ($cfg['client_id'] ?? ''),
                clientSecret: (string) ($cfg['client_secret'] ?? ''),
                posId: (string) ($cfg['pos_id'] ?? ''),
            );
        });

        $this->app->singleton(PayUSignatureVerifier::class, function ($app): PayUSignatureVerifier {
            return new PayUSignatureVerifier(
                md5Key: (string) ($app['config']['services.payu.md5_key'] ?? ''),
            );
        });

        $this->app->singleton(KsefClient::class, function ($app): KsefClient {
            $cfg = (array) $app['config']['services.ksef'];

            return new KsefClient(
                env: (string) ($cfg['env'] ?? 'test'),
                nip: (string) ($cfg['nip'] ?? ''),
                token: ($cfg['token'] ?? null) === '' ? null : ($cfg['token'] ?? null),
            );
        });
    }
}
