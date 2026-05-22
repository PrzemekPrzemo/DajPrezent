<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Dane sprzedawcy (Sendormeco Holding) — używane przez KSeF i e-maile
|--------------------------------------------------------------------------
|
| Pojedyncze źródło prawdy dla danych wystawcy faktur i nagłówka maili.
| NIP w formacie czystych cyfr (KSeF wymaga 10 cyfr bez separatorów).
|
*/

return [

    'legal_name' => env('SELLER_LEGAL_NAME', 'Sendormeco Holding'),
    'brand' => env('SELLER_BRAND', 'DajPrezent.pl'),
    'nip' => env('SELLER_NIP', '5252866457'),
    'regon' => env('SELLER_REGON'),
    'krs' => env('SELLER_KRS'),

    'address' => [
        'street' => env('SELLER_STREET'),
        'postal_code' => env('SELLER_POSTAL_CODE'),
        'city' => env('SELLER_CITY'),
        'country' => env('SELLER_COUNTRY', 'PL'),
    ],

    'contact' => [
        'email' => env('SELLER_EMAIL', 'kontakt@dajprezent.pl'),
        'phone' => env('SELLER_PHONE'),
    ],

    'bank_account' => env('SELLER_BANK_ACCOUNT'),

    // Numeracja faktur — format z roku/miesiąca i licznika. KSeF wymaga
    // unikalnego numeru w obrębie wystawcy.
    'invoice_number_format' => 'FV/{year}/{month}/{counter:04d}',

];
