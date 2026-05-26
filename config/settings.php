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

        /* SMTP — outgoing mail ---------------------------------------- */
        // 'log' for early dev (writes to laravel.log), 'smtp' for prod.
        'mail.driver' => env('MAIL_MAILER', 'log'),
        'mail.host' => env('MAIL_HOST', ''),
        'mail.port' => env('MAIL_PORT', 587),
        'mail.username' => env('MAIL_USERNAME', ''),
        'mail.password' => env('MAIL_PASSWORD', ''),
        // tls | ssl | null (no encryption — STARTTLS upgrade on connect)
        'mail.encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'mail.from_address' => env('MAIL_FROM_ADDRESS', 'noreply@dajprezent.pl'),
        'mail.from_name' => env('MAIL_FROM_NAME', 'DajPrezent.pl'),

        /* SEO / SEM tracking ------------------------------------------- */
        // Google Analytics 4 Measurement ID, e.g. G-MEFY74Q5GK. Empty
        // means "don't include gtag.js" (Plausible-only / no tracking).
        'seo.ga4_id' => env('GA4_MEASUREMENT_ID', ''),
        // Google Search Console HTML meta tag content (the 44-char
        // string from "Recommended → HTML tag" verification flow).
        'seo.gsc_verification' => env('GSC_VERIFICATION', ''),
        // Google Ads Conversion ID (AW-...) for funnel tracking,
        // optional — empty if no SEM campaigns.
        'seo.google_ads_id' => env('GOOGLE_ADS_ID', ''),
        // Privacy-friendly analytics — Plausible domain to track to.
        // Empty disables.
        'seo.plausible_domain' => env('PLAUSIBLE_DOMAIN', ''),
    ],
];
