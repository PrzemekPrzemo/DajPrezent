<?php

declare(strict_types=1);

namespace App\Domain\Seo;

use App\Domain\Billing\Models\Package;
use App\Domain\Wedding\Models\WeddingEvent;

/**
 * Tiny helpers that build schema.org JSON-LD payloads. We emit them
 * server-side via the x-seo.jsonld Blade component. Keeping the
 * factory methods static makes them easy to call from a view or a
 * controller without DI ceremony.
 */
final class JsonLd
{
    /** @return array<string, mixed> */
    public static function organization(): array
    {
        $seller = (array) config('seller');
        $address = (array) ($seller['address'] ?? []);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $seller['brand'] ?? 'DajPrezent.pl',
            'legalName' => $seller['legal_name'] ?? 'Sendormeco Holding sp. z o.o.',
            'url' => url('/'),
            'logo' => url('/brand/favicon.svg'),
            'taxID' => $seller['nip'] ?? null,
            'vatID' => 'PL'.($seller['nip'] ?? ''),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $address['street'] ?? null,
                'postalCode' => $address['postal_code'] ?? null,
                'addressLocality' => $address['city'] ?? null,
                'addressCountry' => $address['country'] ?? 'PL',
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'email' => $seller['contact']['email'] ?? 'kontakt@dajprezent.pl',
                'contactType' => 'customer service',
                'availableLanguage' => ['Polish', 'English'],
            ],
        ];
    }

    /**
     * @param  list<array{q:string,a:string}>  $items
     * @return array<string, mixed>
     */
    public static function faqPage(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn (array $i) => [
                '@type' => 'Question',
                'name' => $i['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $i['a'],
                ],
            ], $items),
        ];
    }

    /**
     * @param  iterable<Package>  $packages
     * @return array<string, mixed>
     */
    public static function offerCatalog(iterable $packages): array
    {
        $offers = [];
        foreach ($packages as $p) {
            $offers[] = [
                '@type' => 'Offer',
                'name' => $p->name,
                'price' => number_format($p->price_pln_gr / 100, 2, '.', ''),
                'priceCurrency' => 'PLN',
                'availability' => 'https://schema.org/InStock',
                'url' => url(route('public.checkout.buy', ['code' => $p->code], absolute: false)),
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Pakiety DajPrezent.pl',
            'itemListElement' => array_map(static function (array $o, int $i): array {
                return ['@type' => 'ListItem', 'position' => $i + 1, 'item' => $o];
            }, $offers, array_keys($offers)),
        ];
    }

    /**
     * @param  list<array{name:string,url:string}>  $crumbs
     * @return array<string, mixed>
     */
    public static function breadcrumbList(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(static fn (array $c, int $i): array => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $c['name'],
                'item' => $c['url'],
            ], $crumbs, array_keys($crumbs)),
        ];
    }

    /** @return array<string, mixed> */
    public static function weddingEvent(WeddingEvent $event, string $publicUrl): array
    {
        $payload = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => ($event->couple_names ?: 'Wedding').' — ślub',
            'url' => $publicUrl,
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'eventStatus' => 'https://schema.org/EventScheduled',
        ];
        if ($event->ceremony_at !== null) {
            $payload['startDate'] = $event->ceremony_at->toIso8601String();
        }
        if ($event->venue_name !== null) {
            $payload['location'] = [
                '@type' => 'Place',
                'name' => $event->venue_name,
                'address' => $event->venue_address,
            ];
        }

        return $payload;
    }
}
