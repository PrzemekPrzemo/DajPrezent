<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Static sitemap of indexable public URLs. Tenant lists are
 * intentionally excluded — they default to noindex and are
 * meant to be shared directly by their owners.
 */
final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            route('home'),
            route('public.pricing'),
            route('public.faq'),
            route('public.contact'),
            route('public.legal.terms'),
            route('public.legal.privacy'),
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.htmlspecialchars($url, ENT_XML1).'</loc></url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
