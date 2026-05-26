@php
    // Master-admin settings — empty values short-circuit each integration.
    /** @var \App\Domain\Settings\SettingsRepository $settings */
    $settings = app(\App\Domain\Settings\SettingsRepository::class);
    $ga4Id     = trim((string) $settings->get('seo.ga4_id', ''));
    $adsId     = trim((string) $settings->get('seo.google_ads_id', ''));
    $gscToken  = trim((string) $settings->get('seo.gsc_verification', ''));
    $plausible = trim((string) $settings->get('seo.plausible_domain', ''));
@endphp

{{-- Google Search Console domain ownership verification --}}
@if ($gscToken !== '')
    <meta name="google-site-verification" content="{{ $gscToken }}">
@endif

{{-- Google Analytics 4 (gtag.js). GA4 ID required; Ads ID adds extra
     config() call when present. Loaded async — never blocks render. --}}
@if ($ga4Id !== '')
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){ dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', @js($ga4Id), { 'anonymize_ip': true });
        @if ($adsId !== '')
            gtag('config', @js($adsId));
        @endif
    </script>
@endif

{{-- Plausible Analytics — privacy-friendly alternative. Can coexist
     with GA4 if admin wants both; usually you pick one. --}}
@if ($plausible !== '')
    <script defer data-domain="{{ $plausible }}" src="https://plausible.io/js/script.js"></script>
@endif
