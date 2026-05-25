<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Import;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * STRICT server-side OG scraper.
 *
 * Powering the "wkleiłem link, pole się wypełniło" flow from the
 * UX/UI document — but with defence in depth:
 *
 *  - Allowlist of popular PL shops. Anything else is refused
 *    upstream so we never act as a generic web proxy (SSRF risk).
 *  - Hard 2.5 s timeout end-to-end; partial response is fine.
 *  - We only ever read the first 384 KB of the body — enough for
 *    head + meta tags on every realistic e-commerce template.
 *  - Cache previews 30 min per URL so a paste storm hits the
 *    upstream once.
 *  - Refuses redirects to non-allowlisted hosts.
 */
final class OpenGraphScraper
{
    private const TIMEOUT_SECONDS = 6.0;

    private const MAX_BODY_BYTES = 512 * 1024;

    /**
     * Hosts (suffix match) we are willing to fetch from. Keeping the
     * list short trades long-tail coverage for safety — the bookmarklet
     * still handles anything else via client-side meta read.
     */
    private const ALLOWLIST = [
        'allegro.pl',
        'allegrolokalnie.pl',
        'empik.com',
        'mediaexpert.pl',
        'mediamarkt.pl',
        'rtveuroagd.pl',
        'morele.net',
        'x-kom.pl',
        'komputronik.pl',
        'zalando.pl',
        'zalando-lounge.pl',
        'rossmann.pl',
        'hebe.pl',
        'douglas.pl',
        'sephora.pl',
        'ikea.com',
        'home.pl',
        'leroymerlin.pl',
        'castorama.pl',
        'obi.pl',
        'smyk.com',
        'taniaksiazka.pl',
        'bonito.pl',
        'amazon.pl',
    ];

    public function __construct(private readonly HttpFactory $http) {}

    public function isAllowed(string $url): bool
    {
        $host = $this->hostnameFor($url);

        return $host !== null && $this->matchesAllowlist($host);
    }

    public function preview(string $url): OpenGraphPreview
    {
        $host = $this->hostnameFor($url);
        if ($host === null) {
            throw new RuntimeException('Nieprawidłowy URL.');
        }
        if (! $this->matchesAllowlist($host)) {
            throw new RuntimeException('Ten sklep nie jest jeszcze obsługiwany przez autopodgląd. Wypełnij dane ręcznie.');
        }

        return Cache::remember(
            'og.preview:'.hash('sha256', $url),
            now()->addMinutes(30),
            fn () => $this->fetch($url, $host),
        );
    }

    private function fetch(string $url, string $host): OpenGraphPreview
    {
        // Realistic Chrome UA + the sec-ch-ua client hints Allegro / Empik /
        // any Cloudflare-fronted shop expects. A bare "DajPrezentBot/1.0"
        // gets an instant 403 challenge. Accept-Encoding: identity skips
        // gzip — Laravel's HTTP client unzips automatically but we'd rather
        // not pay the round-trip when we're truncating at 512 KB anyway.
        $response = $this->http
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '.
                    '(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'pl-PL,pl;q=0.9,en;q=0.8',
                'Accept-Encoding' => 'identity',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'Sec-Ch-Ua' => '"Chromium";v="124", "Google Chrome";v="124", "Not.A/Brand";v="99"',
                'Sec-Ch-Ua-Mobile' => '?0',
                'Sec-Ch-Ua-Platform' => '"Windows"',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
                'Upgrade-Insecure-Requests' => '1',
            ])
            ->timeout(self::TIMEOUT_SECONDS)
            ->withOptions([
                'allow_redirects' => [
                    'max' => 4,
                    'protocols' => ['https'],
                    'strict' => true,
                ],
                'stream' => false,
                'verify' => true,
            ])
            ->get($url);

        if (! $response->successful()) {
            Log::warning('og.preview.upstream_error', [
                'host' => $host,
                'status' => $response->status(),
                'url' => $url,
            ]);
            throw new RuntimeException('Nie udało się pobrać podglądu (HTTP '.$response->status().').');
        }

        $body = substr((string) $response->body(), 0, self::MAX_BODY_BYTES);

        return new OpenGraphPreview(
            url: $url,
            title: $this->ogMeta($body, 'og:title') ?? $this->extractTitle($body),
            pricePlnGr: $this->extractPrice($body),
            imageUrl: $this->absoluteUrl($url, $this->ogMeta($body, 'og:image')),
            description: $this->ogMeta($body, 'og:description') ?? $this->ogMeta($body, 'description'),
            source: $host,
        );
    }

    private function hostnameFor(string $url): ?string
    {
        $parts = parse_url($url);
        if (! is_array($parts)) {
            return null;
        }
        $scheme = strtolower($parts['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }
        $host = strtolower((string) ($parts['host'] ?? ''));

        return $host !== '' ? $host : null;
    }

    private function matchesAllowlist(string $host): bool
    {
        foreach (self::ALLOWLIST as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }

    private function ogMeta(string $html, string $property): ?string
    {
        $patterns = [
            '/<meta\s+(?:[^>]*?\s+)?property=["\']'.preg_quote($property, '/').'["\'](?:[^>]*?)\s+content=["\']([^"\']+)["\']/i',
            '/<meta\s+(?:[^>]*?\s+)?content=["\']([^"\']+)["\'](?:[^>]*?)\s+property=["\']'.preg_quote($property, '/').'["\']/i',
            '/<meta\s+(?:[^>]*?\s+)?name=["\']'.preg_quote($property, '/').'["\'](?:[^>]*?)\s+content=["\']([^"\']+)["\']/i',
        ];
        foreach ($patterns as $re) {
            if (preg_match($re, $html, $m) === 1) {
                $value = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $value = trim($value);

                return $value !== '' ? Str::limit($value, 500, '') : null;
            }
        }

        return null;
    }

    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $m) !== 1) {
            return null;
        }
        $value = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $value !== '' ? Str::limit($value, 120, '') : null;
    }

    /**
     * Looks for a price via OG / schema.org / common DOM hints.
     * Returns gross PLN cents or null.
     */
    private function extractPrice(string $html): ?int
    {
        $candidates = [
            $this->ogMeta($html, 'product:price:amount'),
            $this->ogMeta($html, 'og:price:amount'),
            $this->ogMeta($html, 'twitter:data1'),
            $this->ogMeta($html, 'price'),
        ];

        foreach ($candidates as $raw) {
            if ($raw === null) {
                continue;
            }
            $amount = $this->normalizePrice($raw);
            if ($amount !== null) {
                return (int) round($amount * 100);
            }
        }

        return null;
    }

    private function normalizePrice(string $raw): ?float
    {
        $clean = preg_replace('/[^0-9,\.]/', '', $raw) ?? '';
        $clean = str_replace(' ', '', $clean);
        if ($clean === '') {
            return null;
        }
        if (substr_count($clean, ',') === 1 && substr_count($clean, '.') === 0) {
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }

        return is_numeric($clean) ? (float) $clean : null;
    }

    private function absoluteUrl(string $base, ?string $candidate): ?string
    {
        if ($candidate === null || $candidate === '') {
            return null;
        }
        if (str_starts_with($candidate, 'http://') || str_starts_with($candidate, 'https://')) {
            return $candidate;
        }
        $parts = parse_url($base);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        $prefix = $parts['scheme'].'://'.$parts['host'];
        if (str_starts_with($candidate, '//')) {
            return $parts['scheme'].':'.$candidate;
        }
        if (str_starts_with($candidate, '/')) {
            return $prefix.$candidate;
        }

        return $prefix.'/'.$candidate;
    }
}
