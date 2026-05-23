<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Static sitemap of indexable public URLs. Tenant lists are
 * intentionally excluded — they default to noindex and are
 * meant to be shared directly by their owners.
 *
 * Each URL ships with `<changefreq>` + `<lastmod>` hints —
 * Google ignores them for ranking but uses them for crawl
 * scheduling, which speeds up indexing of fresh content.
 */
final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $today = now()->toDateString();

        $urls = [
            ['loc' => route('home'),                       'changefreq' => 'weekly',  'priority' => '1.0'],
            ['loc' => route('public.pricing'),             'changefreq' => 'weekly',  'priority' => '0.9'],
            ['loc' => route('public.landing.birthday'),    'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('public.landing.wedding'),     'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('public.landing.anniversary'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('public.faq'),                 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => route('public.help.index'),          'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => route('public.contact'),             'changefreq' => 'yearly',  'priority' => '0.4'],
            ['loc' => route('public.legal.terms'),         'changefreq' => 'yearly',  'priority' => '0.3'],
            ['loc' => route('public.legal.privacy'),       'changefreq' => 'yearly',  'priority' => '0.3'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url>'
                .'<loc>'.htmlspecialchars($url['loc'], ENT_XML1).'</loc>'
                .'<lastmod>'.$today.'</lastmod>'
                .'<changefreq>'.$url['changefreq'].'</changefreq>'
                .'<priority>'.$url['priority'].'</priority>'
                .'</url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
