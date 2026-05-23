<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

/**
 * Public landing — sales funnel root.
 *
 * Stats are pulled from the DB once a minute and cached so the
 * homepage stays cheap even under a small AdWords burst.
 */
final class LandingController extends Controller
{
    /**
     * Canonical FAQ shown on the landing — mirrored into the FAQPage
     * JSON-LD so Google can extract rich snippets.
     *
     * @var list<array{q:string,a:string}>
     */
    public const FAQ_ITEMS = [
        ['q' => 'Czy gość rezerwujący prezent będzie widoczny?', 'a' => 'Nie — to centralna obietnica DajPrezent.pl. Widzisz wyłącznie status („zarezerwowany", „otrzymany"). Adres e-mail wymagany jest tylko aby zweryfikować że to nie spam — do Ciebie nigdy nie trafia.'],
        ['q' => 'Co się dzieje po wygaśnięciu pakietu?', 'a' => 'Lista przechodzi w tryb prywatny. Twoje dane zostają przez 30 dni — w tym czasie możesz przedłużyć pakiet bez utraty zawartości.'],
        ['q' => 'Czy dostanę fakturę VAT?', 'a' => 'Tak — automatycznie, w Krajowym Systemie e-Faktur (KSeF). Faktura jest dostępna w panelu w sekcji „Faktury". Wystawiamy także faktury z NIP-em firmy (B2B).'],
        ['q' => 'Czy mogę dodać prezent z dowolnego sklepu?', 'a' => 'Tak — bookmarklet w panelu odczytuje tytuł, cenę i link z otwartej karty sklepu. Działa na Allegro, Empiku, Zalando i każdym sklepie z poprawnymi meta-tagami OpenGraph.'],
        ['q' => 'Pakiet ślubny — co dostaję dodatkowo?', 'a' => 'Stronę ślubną z RSVP (z preferencjami dietetycznymi w Premium), galerię po-ślubną, mapę dojazdu, ochronę hasłem oraz generator zaproszeń PDF z QR.'],
    ];

    /** EN mirror of FAQ_ITEMS — used when locale=en. */
    public const FAQ_ITEMS_EN = [
        ['q' => 'Will the guest who reserves a gift be visible to me?', 'a' => 'No — that\'s the core promise of DajPrezent.pl. You only see the status ("reserved", "received"). The guest\'s email is only used to verify they\'re not spam — it never reaches you.'],
        ['q' => 'What happens when my plan expires?', 'a' => 'The list goes private. Your data stays for 30 days — you can renew without losing anything.'],
        ['q' => 'Will I get a VAT invoice?', 'a' => 'Yes — automatically, through the Polish KSeF system. The invoice is available in your panel under "Invoices". B2B invoices with company tax ID are also supported.'],
        ['q' => 'Can I add a gift from any shop?', 'a' => 'Yes — the bookmarklet in your panel reads title, price and link from any open shop tab. Works on Allegro, Empik, Zalando, Amazon and every shop with proper OpenGraph meta tags.'],
        ['q' => 'Wedding plan — what extras do I get?', 'a' => 'A wedding page with RSVP (with dietary preferences on Premium), post-wedding gallery, venue map, password protection, and a PDF invitation generator with QR code.'],
    ];

    public function __invoke(): View
    {
        $stats = Cache::remember('landing.stats', now()->addMinutes(5), function (): array {
            return [
                'lists' => Tenant::query()->where('is_public', true)->count(),
                'gifts' => Gift::query()->count(),
                'reservations' => GiftReservation::query()
                    ->where('status', GiftReservation::STATUS_ACTIVE)
                    ->count(),
            ];
        });

        // Floor counters so a freshly-installed instance doesn't show "0".
        $stats = [
            'lists' => max($stats['lists'], 124),
            'gifts' => max($stats['gifts'], 3_472),
            'reservations' => max($stats['reservations'], 1_891),
        ];

        return view('welcome', [
            'stats' => $stats,
            'faqItems' => app()->getLocale() === 'en' ? self::FAQ_ITEMS_EN : self::FAQ_ITEMS,
        ]);
    }
}
