<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public knowledge base. Articles live as Blade partials in
 * resources/views/public/help/articles/{slug}.blade.php (PL —
 * default fallback) and resources/views/public/help/articles/en/{slug}.blade.php
 * (EN). HelpController::show picks the right partial based on
 * app()->getLocale() and falls back to PL if EN doesn't exist for a
 * given article (avoids 404 on locale flip mid-rollout).
 *
 * Slug taxonomy + titles live in ARTICLES (PL) / ARTICLES_EN. URL
 * slugs stay PL even on /en — they're shared across locales so
 * external links don't break when a user switches language.
 */
final class HelpController extends Controller
{
    public const ARTICLES = [
        'jak-zarezerwowac-prezent' => 'Jak zarezerwować prezent z listy',
        'anonimowosc-rezerwacji' => 'Czy właściciel zobaczy że to ja zarezerwowałem',
        'dodawanie-prezentow' => 'Jak dodać prezenty do listy',
        'udostepnianie-listy' => 'Jak udostępnić listę bliskim',
        'haslo-do-listy' => 'Jak chronić listę hasłem',
        'wedding-rsvp' => 'Wesele — RSVP i lista prezentów',
        'faktura-vat' => 'Faktura VAT i KSeF',
        'wygasniecie-pakietu' => 'Co się dzieje po wygaśnięciu pakietu',
    ];

    public const ARTICLES_EN = [
        'jak-zarezerwowac-prezent' => 'How to reserve a gift from the list',
        'anonimowosc-rezerwacji' => 'Will the host see that it was me who reserved',
        'dodawanie-prezentow' => 'How to add gifts to your list',
        'udostepnianie-listy' => 'How to share the list with loved ones',
        'haslo-do-listy' => 'How to password-protect a list',
        'wedding-rsvp' => 'Wedding — RSVP and gift list',
        'faktura-vat' => 'VAT invoice and Polish KSeF',
        'wygasniecie-pakietu' => 'What happens when the plan expires',
    ];

    public function index(): View
    {
        $articles = app()->getLocale() === 'en' ? self::ARTICLES_EN : self::ARTICLES;

        return view('public.help.index', ['articles' => $articles]);
    }

    public function show(string $slug): View
    {
        if (! array_key_exists($slug, self::ARTICLES)) {
            throw new NotFoundHttpException;
        }

        $isEn = app()->getLocale() === 'en';
        $title = $isEn ? self::ARTICLES_EN[$slug] : self::ARTICLES[$slug];

        // EN partial path; fall back to PL when EN article hasn't shipped.
        $partial = 'public.help.articles.'.$slug;
        if ($isEn && view()->exists('public.help.articles.en.'.$slug)) {
            $partial = 'public.help.articles.en.'.$slug;
        }

        return view('public.help.show', [
            'slug' => $slug,
            'title' => $title,
            'partial' => $partial,
        ]);
    }
}
