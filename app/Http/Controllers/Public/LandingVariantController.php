<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * SEO landing variants per long-tail keyword. Each variant reuses
 * a single Blade template but injects its own H1, lead, schema-org
 * payload and CTA — letting us rank for separate intents (birthday,
 * wedding, anniversary) without forking the whole landing.
 */
final class LandingVariantController extends Controller
{
    public function birthday(): View
    {
        return $this->render([
            'route_name' => 'public.landing.birthday',
            'title' => 'Lista prezentów na urodziny',
            'h1' => 'Lista prezentów na urodziny — bez duplikatów, bez stresu',
            'lead' => 'Bliscy zapytają Cię „co chcesz w prezencie?" już dziesięć razy. Zrób listę raz, podziel się linkiem — każdy zarezerwuje co chce, anonimowo. Ty się dowiesz przy rozpakowywaniu.',
            'meta_description' => 'Stwórz listę prezentów na urodziny w 3 minuty. Bliscy rezerwują anonimowo, bez konta. Bez duplikatów, z fakturą VAT. Od 0 zł.',
            'cta_package' => 'mini',
            'tag' => 'urodziny',
            'kicker' => '🎂 Najczęstsza okazja',
            'use_cases' => [
                ['icon' => '🎁', 'title' => 'Konkretne pomysły', 'body' => 'Wpisujesz tytuły i linki ze sklepów. Bliscy widzą dokładnie o co chodzi — bez zgadywania.'],
                ['icon' => '🛡', 'title' => 'Bez duplikatów', 'body' => 'Każdy prezent można zarezerwować tylko raz — nikt nie kupi tego samego co ciocia.'],
                ['icon' => '🔒', 'title' => 'Twoje zaproszenie', 'body' => 'Decydujesz komu wysłać link. Lista nie wyświetla się w Google (noindex).'],
            ],
        ]);
    }

    public function wedding(): View
    {
        return $this->render([
            'route_name' => 'public.landing.wedding',
            'title' => 'Lista prezentów ślubnych',
            'h1' => 'Lista prezentów ślubnych z RSVP w jednym miejscu',
            'lead' => 'Strona ślubna, formularz RSVP z preferencjami dietetycznymi, harmonogram, mapa dojazdu i lista prezentów — wszystko pod jednym, własnym adresem. Bez kopert, bez sześciu identycznych ekspresów.',
            'meta_description' => 'Lista prezentów ślubnych z RSVP, harmonogramem i mapą dojazdu. Goście rezerwują anonimowo, dieta zaznacza się w formularzu. Pakiety od 199 zł.',
            'cta_package' => 'wedding_basic',
            'tag' => 'wesele',
            'kicker' => '💍 Pakiet ślubny',
            'use_cases' => [
                ['icon' => '📅', 'title' => 'RSVP + dieta', 'body' => 'Goście potwierdzają obecność, zaznaczają dietę, plus-one. Eksport CSV dla cateringu jednym klikiem.'],
                ['icon' => '🗺', 'title' => 'Strona ceremonii', 'body' => 'Hero z imionami, harmonogram, mapa do kościoła i sali. Wszystko brandowane pod Was.'],
                ['icon' => '🎁', 'title' => 'Lista prezentów', 'body' => 'Bez koperty, bez duplikatów. Goście rezerwują anonimowo — Wy widzicie tylko status.'],
            ],
        ]);
    }

    public function anniversary(): View
    {
        return $this->render([
            'route_name' => 'public.landing.anniversary',
            'title' => 'Prezent na rocznicę',
            'h1' => 'Prezent na rocznicę — wspólnie wybrany, indywidualnie wymarzony',
            'lead' => 'Rocznica nie musi kończyć się kwiatami z benzynowej. Stwórz listę wspólnych marzeń (sprzęt do kuchni, weekend SPA, książka, voucher do restauracji) — bliscy zarezerwują anonimowo, a Wy świętujecie.',
            'meta_description' => 'Lista prezentów na rocznicę ślubu, urodzin firmy, jubileuszu. Wspólne marzenia w jednym miejscu — bliscy rezerwują, Wy świętujecie.',
            'cta_package' => 'standard',
            'tag' => 'rocznica',
            'kicker' => '💝 Pamięć w prezencie',
            'use_cases' => [
                ['icon' => '🌹', 'title' => 'Coś wspólnego', 'body' => 'Wpisujesz to o czym oboje marzycie — od wakacji po nowy ekspres. Bliscy dorzucają się anonimowo.'],
                ['icon' => '📝', 'title' => 'Lista z historią', 'body' => 'Lista zostaje na 9 miesięcy — możesz wrócić, dodać kolejne, zachować pamiątkę.'],
                ['icon' => '🧾', 'title' => 'Faktura VAT', 'body' => 'Każdy zakup pakietu kończy się fakturą w KSeF — także dla firm jubilatów.'],
            ],
        ]);
    }

    /** @param  array<string, mixed>  $data */
    private function render(array $data): View
    {
        return view('public.landing-variant', $data);
    }
}
