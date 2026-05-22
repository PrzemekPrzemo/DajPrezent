<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| DajPrezent — definicje pakietów (źródło prawdy)
|--------------------------------------------------------------------------
|
| Każdy pakiet ma kod (stabilny identyfikator), nazwę marketingową, cenę
| brutto w groszach (unikamy floatów), długość ważności w dniach oraz
| flagi funkcji wykorzystywane przez gates/policy. Te wartości są też
| seedowane do tabeli `packages` (config + DB dla łatwych zmian z panelu
| master admina, ale config pozostaje fallbackiem dla świeżych instancji).
|
*/

return [

    'standard' => [
        'free' => [
            'name' => 'Free / Trial',
            'kind' => 'standard',
            'price_pln_gr' => 0,
            'valid_days' => 30,
            'gift_limit' => 3,
            'custom_slug' => false,
            'password_protect' => false,
            'multiple_lists' => false,
            'remove_branding' => false,
            'custom_domain' => false,
            'export' => false,
        ],
        'mini' => [
            'name' => 'Mini',
            'kind' => 'standard',
            'price_pln_gr' => 1900,
            'valid_days' => 270, // 9 mc
            'gift_limit' => 10,
            'custom_slug' => false,
            'password_protect' => false,
            'multiple_lists' => false,
            'remove_branding' => false,
            'custom_domain' => false,
            'export' => false,
        ],
        'standard' => [
            'name' => 'Standard',
            'kind' => 'standard',
            'price_pln_gr' => 3900,
            'valid_days' => 270,
            'gift_limit' => 30,
            'custom_slug' => true,
            'password_protect' => false,
            'multiple_lists' => false,
            'remove_branding' => false,
            'custom_domain' => false,
            'export' => false,
        ],
        'plus' => [
            'name' => 'Plus',
            'kind' => 'standard',
            'price_pln_gr' => 6900,
            'valid_days' => 270,
            'gift_limit' => 75,
            'custom_slug' => true,
            'password_protect' => true,
            'multiple_lists' => 3,
            'remove_branding' => false,
            'custom_domain' => false,
            'export' => false,
        ],
        'pro' => [
            'name' => 'Pro',
            'kind' => 'standard',
            'price_pln_gr' => 9900,
            'valid_days' => 270,
            'gift_limit' => 200,
            'custom_slug' => true,
            'password_protect' => true,
            'multiple_lists' => 5,
            'remove_branding' => true,
            'custom_domain' => true,
            'export' => true,
        ],
    ],

    'wedding' => [
        'wedding_basic' => [
            'name' => 'Wedding Basic',
            'kind' => 'wedding',
            'price_pln_gr' => 19900,
            'valid_days' => 365,
            'gift_limit' => null, // bez limitu
            'custom_slug' => true,
            'password_protect' => true,
            'locales' => ['pl'],
            'gallery' => false,
            'rsvp_dietary' => false,
            'custom_domain' => false,
            'invitation_pdf' => false,
            'priority_support' => false,
        ],
        'wedding_premium' => [
            'name' => 'Wedding Premium',
            'kind' => 'wedding',
            'price_pln_gr' => 39900,
            'valid_days' => 365,
            'gift_limit' => null,
            'custom_slug' => true,
            'password_protect' => true,
            'locales' => ['pl', 'en'],
            'gallery' => true,
            'rsvp_dietary' => true,
            'custom_domain' => true,
            'invitation_pdf' => true,
            'priority_support' => true,
        ],
    ],

    'addons' => [
        'extra_gifts_50' => ['name' => 'Dodatkowe 50 prezentów',           'price_pln_gr' => 1900],
        'extend_standard_3m' => ['name' => 'Przedłużenie pakietu o 3 miesiące', 'price_pln_gr' => 1500],
        'extend_wedding_1y' => ['name' => 'Przedłużenie pakietu ślubnego',     'price_pln_gr' => 9900],
        'custom_theme' => ['name' => 'Indywidualny motyw graficzny',      'price_pln_gr' => 14900],
    ],

    /*
    |---------------------------------------------------------------------
    | Slugi systemowe (czarna lista) — uniemożliwiamy konflikt z route'ami
    |---------------------------------------------------------------------
    */
    'reserved_slugs' => [
        'admin', 'api', 'app', 'auth', 'billing', 'blog', 'cart',
        'checkout', 'contact', 'dashboard', 'docs', 'download', 'edit',
        'faq', 'help', 'home', 'image', 'images', 'invoice', 'invoices',
        'kontakt', 'ksef', 'login', 'logout', 'master', 'me', 'mobile',
        'new', 'oauth', 'panel', 'pay', 'payment', 'payments', 'payu',
        'pl', 'en', 'plan', 'pliki', 'pomoc', 'pricing', 'private',
        'profile', 'public', 'rabat', 'register', 'rejestracja', 'rsvp',
        'settings', 'shop', 'sklep', 'static', 'storage', 'support',
        'system', 'tenant', 'tenants', 'terms', 'test', 'u', 'user',
        'users', 'web', 'webhook', 'webhooks', 'wedding', 'wesele',
        'wishlist', 'www',
    ],

];
