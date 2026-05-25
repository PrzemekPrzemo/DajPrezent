<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Runtime settings — defaults
|--------------------------------------------------------------------------
|
| SettingsRepository::get() falls back to these defaults whenever a
| key has not been overridden in the `app_settings` table. Master
| admin can change these from /admin/settings without redeploy.
|
| Sensitive keys (those ending with _secret / _token / _password / _key)
| are encrypted at rest by SettingsRepository automatically.
|
*/

return [
    'defaults' => [
        /* PayU --------------------------------------------------------- */
        'payu.env' => env('PAYU_ENV', 'sandbox'),
        'payu.base_url' => env('PAYU_BASE_URL', 'https://secure.snd.payu.com'),
        'payu.pos_id' => env('PAYU_POS_ID', ''),
        'payu.client_id' => env('PAYU_CLIENT_ID', ''),
        'payu.client_secret' => env('PAYU_CLIENT_SECRET', ''),
        'payu.md5_key' => env('PAYU_MD5_KEY', ''),

        /* KSeF --------------------------------------------------------- */
        // env: test | demo | prod — controls which MF endpoint and
        // whether KsefClient throws on missing credentials.
        'ksef.env' => env('KSEF_ENV', 'test'),
        // Issuer NIP (10 digits). Required for both auth modes.
        'ksef.nip' => env('KSEF_NIP', ''),
        // Legacy token-auth (the simplest auth path — KSeF "token
        // authoryzacyjny" generated in MF account).
        'ksef.token' => env('KSEF_TOKEN', ''),
        // Certificate-based auth (.pfx file uploaded via the panel,
        // stored under storage/app/private/ksef/). Path is relative
        // to that directory; password is encrypted at rest.
        'ksef.cert_path' => '',
        'ksef.cert_password' => '',

        /* Invoice numbering -------------------------------------------- */
        // Format placeholders: {YYYY} {YY} {MM} {DD} {N} {NNNN}
        // Default produces FV/2026/05/0001.
        'invoice.number_format' => 'FV/{YYYY}/{MM}/{NNNN}',
        // monthly | yearly | never — when the sequence resets to 1.
        'invoice.sequence_reset' => 'monthly',
        // First invoice number after migration / fresh install.
        'invoice.start_number' => 1,
    ],
];
