@extends('layouts.public')

@section('title', 'FAQ — najczęstsze pytania')
@section('og_image', route('public.og', ['title' => 'Najczęstsze pytania', 'subtitle' => 'DajPrezent.pl FAQ']))

@push('head_extra')
    <x-seo.jsonld :data="\App\Domain\Seo\JsonLd::faqPage(\App\Http\Controllers\Public\LandingController::FAQ_ITEMS)"/>
    <x-seo.jsonld :data="\App\Domain\Seo\JsonLd::breadcrumbList([
        ['name' => 'Strona główna', 'url' => url('/')],
        ['name' => 'FAQ', 'url' => route('public.faq')],
    ])"/>
@endpush

@php
    $items = [
        ['q' => 'Czy gość, który zarezerwuje prezent, widoczny jest dla mnie?', 'a' => 'Nie — to centralna obietnica DajPrezent.pl. Widzisz wyłącznie status („zarezerwowany", „otrzymany"). Tożsamość darczyńcy poznasz dopiero, gdy faktycznie wręczy Ci prezent. Adres e-mail osoby rezerwującej wymagamy wyłącznie po to, żeby zweryfikować, że to nie spam — wysyłamy do niej link aktywacyjny, ale ten adres nigdy do Ciebie nie trafia.'],
        ['q' => 'Czy mogę założyć kilka list?', 'a' => 'Tak — pakiety <strong>Plus</strong> i wyżej pozwalają na wiele list. Każda ma osobny slug i osobne prezenty.'],
        ['q' => 'Co się stanie po wygaśnięciu pakietu?', 'a' => 'Lista przechodzi w tryb prywatny — adres <code>dajprezent.pl/&lt;slug&gt;</code> zwraca informację o wygaśnięciu, ale Twoje dane i prezenty nie są kasowane przez 30 dni. W tym czasie możesz przedłużyć pakiet bez utraty zawartości.'],
        ['q' => 'Jak ochronić listę hasłem?', 'a' => 'W pakietach <strong>Plus</strong> i wyższych: zaloguj się do panelu, otwórz Ustawienia listy i wpisz hasło. Goście będą musieli je podać przy wejściu na publiczną stronę.'],
        ['q' => 'Czy dostanę fakturę VAT?', 'a' => 'Tak — faktura wystawiana jest automatycznie po opłaceniu pakietu i trafia do KSeF. Dostępna jest w panelu w sekcji „Faktury". Potrzebujesz faktury z innymi danymi nabywcy? Napisz na <a href="mailto:faktury@dajprezent.pl" class="text-dp-purple-700 hover:underline">faktury@dajprezent.pl</a>.'],
        ['q' => 'Czy mogę dodać prezent z dowolnego sklepu?', 'a' => 'Tak — w panelu dostępny jest „Bookmarklet" (mały przycisk do paska zakładek). Kliknij go na stronie sklepu, a otworzy się okienko z gotowym do dodania prezentem (tytuł, link, cena pobrane z meta-tagów strony).'],
        ['q' => 'Pakiet ślubny — co dostaję dodatkowo?', 'a' => 'Stronę ślubną z informacjami o uroczystości, formularzem RSVP (z preferencjami dietetycznymi w wersji Premium), galerią zdjęć po ślubie, mapą dojazdu, ochroną hasłem oraz generatorem zaproszeń PDF z QR.'],
        ['q' => 'Czy DajPrezent.pl jest serwisem polskim?', 'a' => 'Tak — operatorem jest Sendormeco Holding sp. z o.o. (NIP 5252866457). Płatności obsługuje PayU, faktury wystawiamy przez KSeF, dane przechowujemy w Unii Europejskiej.'],
        ['q' => 'Jak skontaktować się z supportem?', 'a' => 'E-mail: <a href="mailto:kontakt@dajprezent.pl" class="text-dp-purple-700 hover:underline">kontakt@dajprezent.pl</a>. Faktury: <a href="mailto:faktury@dajprezent.pl" class="text-dp-purple-700 hover:underline">faktury@dajprezent.pl</a>. RODO: <a href="mailto:rodo@dajprezent.pl" class="text-dp-purple-700 hover:underline">rodo@dajprezent.pl</a>.'],
    ];
@endphp

@section('content')
    <header class="max-w-3xl mx-auto px-4 pt-16 pb-6 text-center">
        <span class="dp-chip dp-chip-pink mb-3 inline-block">💡 Centrum wiedzy</span>
        <h1 class="font-display text-4xl sm:text-5xl font-bold m-0">Najczęstsze pytania</h1>
        <p class="text-dp-muted mt-3">Krótkie odpowiedzi na to, co najczęściej pojawia się przed pierwszym zakupem. Coś jeszcze? <a href="{{ route('public.contact') }}" class="text-dp-purple-700 font-semibold hover:underline">Napisz do nas</a>.</p>
    </header>

    <section class="max-w-3xl mx-auto px-4 pb-16" x-data="{ open: 0 }">
        <div class="space-y-3">
            @foreach ($items as $i => $item)
                <div class="dp-card !p-0 overflow-hidden">
                    <button type="button" @click="open = (open === {{ $i }} ? null : {{ $i }})"
                            class="w-full text-left px-6 py-4 flex items-center justify-between gap-3 hover:bg-dp-purple-50/50 transition">
                        <span class="font-display font-semibold text-base">{{ $item['q'] }}</span>
                        <span class="text-dp-purple-500 text-xl transition-transform shrink-0"
                              :class="open === {{ $i }} ? 'rotate-45' : ''">+</span>
                    </button>
                    <div x-show="open === {{ $i }}" x-cloak x-collapse>
                        <div class="px-6 pb-5 text-sm text-dp-muted leading-relaxed">{!! $item['a'] !!}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-12 dp-card text-center bg-dp-purple-50/40 border-dp-purple-100">
            <h2 class="font-display text-2xl font-bold m-0">Nadal masz pytanie?</h2>
            <p class="text-sm text-dp-muted mt-2">Odpowiadamy w ciągu 1 dnia roboczego.</p>
            <div class="mt-5 flex flex-wrap justify-center gap-3">
                <a href="{{ route('public.contact') }}" class="dp-btn-primary px-6 py-2.5">Napisz do nas →</a>
                <a href="{{ route('public.pricing') }}" class="dp-btn-secondary px-4 py-2.5">Zobacz pakiety</a>
            </div>
        </div>
    </section>
@endsection
