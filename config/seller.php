<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Dane sprzedawcy (Sendormeco Holding sp. z o.o.) — KSeF / FV / nagłówek
|--------------------------------------------------------------------------
|
| Pojedyncze źródło prawdy dla danych wystawcy faktur i informacji
| prawnych w stopkach, regulaminie i polityce prywatności.
|
| Dane spółki potwierdzone w publicznym rejestrze KRS (0000906110):
|   - REGON 389194801, NIP 5252866457
|   - siedziba: ul. Złota 75A/7, 00-819 Warszawa
|   - kapitał zakładowy: 5 000 zł
|
| NIP w formacie czystych cyfr (KSeF wymaga 10 cyfr bez separatorów).
*/

return [

    'legal_name' => env('SELLER_LEGAL_NAME', 'Sendormeco Holding sp. z o.o.'),
    'brand' => env('SELLER_BRAND', 'DajPrezent.pl'),

    'nip' => env('SELLER_NIP', '5252866457'),
    'regon' => env('SELLER_REGON', '389194801'),
    'krs' => env('SELLER_KRS', '0000906110'),

    'address' => [
        'street' => env('SELLER_STREET', 'ul. Złota 75A/7'),
        'postal_code' => env('SELLER_POSTAL_CODE', '00-819'),
        'city' => env('SELLER_CITY', 'Warszawa'),
        'country' => env('SELLER_COUNTRY', 'PL'),
    ],

    'contact' => [
        'email' => env('SELLER_EMAIL', 'kontakt@dajprezent.pl'),
        'phone' => env('SELLER_PHONE'),
    ],

    'bank_account' => env('SELLER_BANK_ACCOUNT'),

    // Wymagane przez Kodeks Spółek Handlowych w obrocie spółek z o.o.
    'share_capital_pln' => (int) env('SELLER_SHARE_CAPITAL_PLN', 5000),
    'registry_court' => env(
        'SELLER_REGISTRY_COURT',
        'Sąd Rejonowy dla m.st. Warszawy w Warszawie, XII Wydział Gospodarczy KRS'
    ),

    // Numeracja faktur — format z roku/miesiąca i licznika. KSeF wymaga
    // unikalnego numeru w obrębie wystawcy.
    'invoice_number_format' => 'FV/{year}/{month}/{counter:04d}',

];
