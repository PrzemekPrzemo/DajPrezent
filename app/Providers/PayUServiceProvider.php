<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Billing\PayU\PayUClient;
use App\Domain\Billing\PayU\PayUSignatureVerifier;
use App\Domain\Invoicing\Ksef\KsefClient;
use App\Domain\Settings\SettingsRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

/**
 * Wires PayU + KSeF clients with credentials sourced from the
 * runtime SettingsRepository first, then falling back to the
 * `.env`-driven config/services.php and config/settings.php
 * defaults. That way master admin can rotate creds from the panel
 * without redeploying.
 *
 * KsefClient supports two auth modes:
 *   - legacy token (single-string token from KSeF web account), and
 *   - certificate (.pfx uploaded via panel, stored under
 *     storage/app/private/ksef/, password encrypted at rest).
 */
final class PayUServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class);

        $this->app->singleton(PayUClient::class, function ($app): PayUClient {
            $s = $app->make(SettingsRepository::class);

            return new PayUClient(
                http: $app->make(HttpFactory::class),
                baseUrl: (string) $s->get('payu.base_url', 'https://secure.snd.payu.com'),
                clientId: (string) $s->get('payu.client_id', ''),
                clientSecret: (string) $s->get('payu.client_secret', ''),
                posId: (string) $s->get('payu.pos_id', ''),
            );
        });

        $this->app->singleton(PayUSignatureVerifier::class, function ($app): PayUSignatureVerifier {
            $s = $app->make(SettingsRepository::class);

            return new PayUSignatureVerifier(
                md5Key: (string) $s->get('payu.md5_key', ''),
            );
        });

        $this->app->singleton(KsefClient::class, function ($app): KsefClient {
            $s = $app->make(SettingsRepository::class);

            $certPath = (string) $s->get('ksef.cert_path', '');
            $certPassword = (string) $s->get('ksef.cert_password', '');
            $token = (string) $s->get('ksef.token', '');

            return new KsefClient(
                env: (string) $s->get('ksef.env', 'test'),
                nip: (string) $s->get('ksef.nip', ''),
                token: $token === '' ? null : $token,
                certPath: $certPath === '' ? null : $certPath,
                certPassword: $certPassword === '' ? null : $certPassword,
            );
        });
    }
}
