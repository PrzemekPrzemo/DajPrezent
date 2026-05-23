<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public knowledge base. Articles live as Blade partials in
 * resources/views/public/help/articles/{slug}.blade.php, registered
 * in self::ARTICLES so we keep the index + titles in one place.
 *
 * KISS approach for MVP: no markdown parser, no DB, no admin editor —
 * Pull-Request based content. Easy to swap for Filament later when we
 * have non-technical editors.
 */
final class HelpController extends Controller
{
    /**
     * Stable URL slug → display title. Order here is order on /pomoc.
     */
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

    public function index(): View
    {
        return view('public.help.index', ['articles' => self::ARTICLES]);
    }

    public function show(string $slug): View
    {
        if (! array_key_exists($slug, self::ARTICLES)) {
            throw new NotFoundHttpException;
        }

        return view('public.help.show', [
            'slug' => $slug,
            'title' => self::ARTICLES[$slug],
        ]);
    }
}
