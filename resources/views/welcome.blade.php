@extends('layouts.public')

@section('title', __('messages.landing.tagline'))
@section('meta_description', __('messages.landing.hero_lead'))
@section('og_image', route('public.og'))

@push('head_extra')
    <x-seo.jsonld :data="\App\Domain\Seo\JsonLd::organization()"/>
    <x-seo.jsonld :data="\App\Domain\Seo\JsonLd::faqPage($faqItems ?? [])"/>
@endpush

@section('content')
    {{-- Sticky CTA bar po przewinięciu --}}
    <div x-data="{ show: false }"
         x-init="window.addEventListener('scroll', () => { show = window.scrollY > 600 })"
         x-show="show" x-cloak x-transition.opacity
         class="fixed bottom-4 left-1/2 -translate-x-1/2 z-40 max-w-md w-[calc(100%-2rem)]">
        <a href="{{ route('public.pricing') }}"
           class="dp-btn-primary w-full justify-center shadow-dp-card-lg py-3">
            {{ __('messages.landing.hero_cta_primary') }}
        </a>
    </div>

    {{-- HERO --}}
    <section class="dp-hero-bg relative overflow-hidden">
        <div class="absolute -top-32 -right-32 w-[480px] h-[480px] bg-dp-purple-200/40 rounded-full blur-3xl dp-bloom"></div>
        <div class="absolute -bottom-40 -left-32 w-[420px] h-[420px] bg-blue-200/30 rounded-full blur-3xl dp-bloom" style="animation-delay: -7s;"></div>

        <div class="relative max-w-6xl mx-auto px-4 py-20 sm:py-28 grid lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-7">
                <span class="dp-chip dp-chip-pink mb-4">{{ __('messages.landing.hero_chip') }}</span>
                <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight">
                    {{ __('messages.landing.hero_h1_pre') }}
                    <span class="bg-dp-gradient bg-clip-text text-transparent">{{ __('messages.landing.hero_h1_emph') }}</span>,
                    {{ __('messages.landing.hero_h1_post') }}
                </h1>
                <p class="mt-5 text-lg text-dp-muted max-w-xl">
                    {{ __('messages.landing.hero_lead') }}
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ route('public.pricing') }}" data-dp-magnet
                       class="dp-btn-primary px-7 py-3 text-base transition-transform">
                        {{ __('messages.landing.hero_cta_primary') }}
                    </a>
                    <a href="{{ route('login') }}" class="dp-btn-ghost px-4 py-3">{{ __('messages.nav.have_account') }}</a>
                </div>
                <div class="mt-6 flex items-center gap-6 text-sm text-dp-muted">
                    <span class="flex items-center gap-1.5">{{ __('messages.landing.hero_feature_no_ads') }}</span>
                    <span class="flex items-center gap-1.5">{{ __('messages.landing.hero_feature_invoice') }}</span>
                    <span class="flex items-center gap-1.5">{{ __('messages.landing.hero_feature_no_account') }}</span>
                </div>
            </div>

            <div class="lg:col-span-5 relative">
                {{-- Floating SVG hero illustration — pure CSS animation,
                     no external lib. Sits above the mock card. --}}
                <div class="absolute -top-14 -right-4 w-36 sm:w-44 z-10 pointer-events-none">
                    <x-brand.hero-illustration/>
                </div>

                <div class="dp-card shadow-dp-card-lg rotate-1 hover:rotate-0 transition-transform duration-700 ease-dp dp-tilt"
                     x-data="{ tilt(e) {
                            const r = this.$el.getBoundingClientRect();
                            const x = (e.clientX - r.left) / r.width  - 0.5;
                            const y = (e.clientY - r.top)  / r.height - 0.5;
                            this.$el.style.transform = `perspective(900px) rotateX(${-y * 4}deg) rotateY(${x * 6}deg)`;
                        }, reset() { this.$el.style.transform = ''; } }"
                     @mousemove="tilt($event)" @mouseleave="reset()">
                    <div class="flex items-center gap-2 pb-3 border-b border-dp-purple-50">
                        <x-brand.logo size="sm"/>
                    </div>
                    <div class="pt-4 space-y-3">
                        <div class="flex items-center gap-3 p-3 rounded-dp bg-dp-purple-50/50">
                            <div class="w-10 h-10 rounded-dp bg-dp-gradient flex items-center justify-center text-white">🎁</div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm">Aparat instax mini</div>
                                <div class="text-xs text-dp-muted">299,00 zł</div>
                            </div>
                            <span class="dp-chip dp-chip-reserved">zarezerwowany</span>
                        </div>
                        <div class="flex items-center gap-3 p-3 rounded-dp bg-dp-purple-50/50">
                            <div class="w-10 h-10 rounded-dp bg-dp-gradient flex items-center justify-center text-white">📚</div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm">Książka „Atomic Habits"</div>
                                <div class="text-xs text-dp-muted">49,90 zł</div>
                            </div>
                            <button class="dp-btn-primary px-3 py-1.5 text-xs">Zarezerwuj</button>
                        </div>
                        <div class="flex items-center gap-3 p-3 rounded-dp bg-dp-purple-50/50">
                            <div class="w-10 h-10 rounded-dp bg-dp-gradient flex items-center justify-center text-white">🌿</div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm">Monstera Deliciosa</div>
                                <div class="text-xs text-dp-muted">89,00 zł</div>
                            </div>
                            <span class="dp-chip dp-chip-received">otrzymany</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- LIVE STATS (social proof) --}}
    <section class="max-w-6xl mx-auto px-4 py-12 -mt-8">
        <div class="bg-white rounded-dp shadow-dp-card border border-dp-purple-50 px-6 py-8 grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
            <div>
                <div class="font-display text-3xl sm:text-4xl font-bold bg-dp-gradient bg-clip-text text-transparent">
                    <span data-dp-countup="{{ $stats['lists'] }}">{{ number_format($stats['lists'], 0, ',', ' ') }}</span>+
                </div>
                <div class="text-sm text-dp-muted mt-1">{{ __('messages.landing.stats_lists') }}</div>
            </div>
            <div class="sm:border-x sm:border-dp-purple-50">
                <div class="font-display text-3xl sm:text-4xl font-bold bg-dp-gradient bg-clip-text text-transparent">
                    <span data-dp-countup="{{ $stats['gifts'] }}">{{ number_format($stats['gifts'], 0, ',', ' ') }}</span>+
                </div>
                <div class="text-sm text-dp-muted mt-1">{{ __('messages.landing.stats_gifts') }}</div>
            </div>
            <div>
                <div class="font-display text-3xl sm:text-4xl font-bold bg-dp-gradient bg-clip-text text-transparent">
                    <span data-dp-countup="{{ $stats['reservations'] }}">{{ number_format($stats['reservations'], 0, ',', ' ') }}</span>+
                </div>
                <div class="text-sm text-dp-muted mt-1">{{ __('messages.landing.stats_reservations') }}</div>
            </div>
        </div>
    </section>

    {{-- 3 KROKI --}}
    <section class="max-w-6xl mx-auto px-4 py-16 dp-reveal">
        <h2 class="text-center font-display text-3xl font-bold mb-3">{{ __('messages.landing.how_h2') }}</h2>
        <p class="text-center text-dp-muted max-w-xl mx-auto mb-12">{{ __('messages.landing.how_lead') }}</p>
        <div class="grid sm:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-dp bg-dp-gradient text-white flex items-center justify-center font-bold text-xl mb-4 shadow-dp-card">1</div>
                <h3 class="font-display font-semibold text-lg">{{ __('messages.landing.how_step1_title') }}</h3>
                <p class="text-sm text-dp-muted mt-2">
                    {!! __('messages.landing.how_step1_body', ['brandSlug' => '<span class="text-dp-purple-700 font-medium">dajprezent.pl/{you}</span>']) !!}
                </p>
            </div>
            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-dp bg-dp-gradient text-white flex items-center justify-center font-bold text-xl mb-4 shadow-dp-card">2</div>
                <h3 class="font-display font-semibold text-lg">{{ __('messages.landing.how_step2_title') }}</h3>
                <p class="text-sm text-dp-muted mt-2">{{ __('messages.landing.how_step2_body') }}</p>
            </div>
            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-dp bg-dp-gradient text-white flex items-center justify-center font-bold text-xl mb-4 shadow-dp-card">3</div>
                <h3 class="font-display font-semibold text-lg">{{ __('messages.landing.how_step3_title') }}</h3>
                <p class="text-sm text-dp-muted mt-2">{{ __('messages.landing.how_step3_body') }}</p>
            </div>
        </div>
    </section>

    {{-- TESTIMONIALS --}}
    <section class="bg-dp-purple-50/40 py-16 dp-reveal">
        <div class="max-w-6xl mx-auto px-4">
            <p class="text-center text-xs uppercase tracking-wider text-dp-muted mb-3">{{ __('messages.landing.testi_kicker') }}</p>
            <h2 class="text-center font-display text-3xl font-bold mb-10">{{ __('messages.landing.testi_h2') }}</h2>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach ([
                    ['quote' => 'W końcu nie dostałam trzech identycznych ekspresów do kawy. Zrobiłam listę raz, podzieliłam się i było po sprawie.', 'who' => 'Marta, 32 lata', 'tag' => 'lista urodzinowa'],
                    ['quote' => 'Bliscy nawet się zdziwili że można tak prosto. „Wpisałem mail, kliknąłem w mail, kupiłem na Allegro" — koniec.', 'who' => 'Tomek, 45 lat', 'tag' => 'lista świąteczna'],
                    ['quote' => 'Strona ślubna z RSVP i listą prezentów w jednym miejscu, do tego FV automatycznie. Polecam każdej parze planującej wesele.', 'who' => 'Anna & Piotr', 'tag' => 'wesele 2026'],
                ] as $t)
                    <figure class="dp-card">
                        <div class="text-4xl text-dp-purple-300 leading-none mb-2">"</div>
                        <blockquote class="text-sm text-dp-navy leading-relaxed">{{ $t['quote'] }}</blockquote>
                        <figcaption class="mt-4 pt-3 border-t border-dp-purple-50 flex items-center justify-between">
                            <span class="text-sm font-semibold">{{ $t['who'] }}</span>
                            <span class="dp-chip dp-chip-available">{{ $t['tag'] }}</span>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
            <p class="text-center text-xs text-dp-muted mt-6">{{ __('messages.landing.testi_disclaimer') }}</p>
        </div>
    </section>

    {{-- FEATURE GRID --}}
    <section class="max-w-6xl mx-auto px-4 py-16 dp-reveal">
        <h2 class="text-center font-display text-3xl font-bold mb-3">{{ __('messages.landing.features_h2') }}</h2>
        <p class="text-center text-dp-muted max-w-xl mx-auto mb-12">{{ __('messages.landing.features_lead') }}</p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ([
                ['icon' => '🛡', 'title' => 'Anonimowe rezerwacje', 'body' => 'Bliscy potwierdzają mailem, ale Ty nigdy nie widzisz kto. Tożsamość poznasz przy rozpakowaniu.'],
                ['icon' => '🔖', 'title' => 'Bookmarklet „Dodaj"', 'body' => 'Jednym kliknięciem dodajesz prezent z dowolnego sklepu — tytuł, cena i link pobierają się automatycznie.'],
                ['icon' => '📷', 'title' => 'Zdjęcia prezentów', 'body' => 'Uploadujesz JPG/PNG, my optymalizujemy do WebP i pokazujemy w siatce. Bliscy widzą o co naprawdę chodzi.'],
                ['icon' => '🔒', 'title' => 'Ochrona hasłem', 'body' => 'Pakiet Plus i wyżej — chronisz listę hasłem, dajesz tylko zaufanym.'],
                ['icon' => '💍', 'title' => 'Strona ślubna', 'body' => 'Pakiet Wedding — RSVP z dietą, generator zaproszeń z QR, galeria po-ślubna, hasło.'],
                ['icon' => '🧾', 'title' => 'Faktura VAT w KSeF', 'body' => 'Każdy zakup z fakturą VAT — automatycznie w Krajowym Systemie e-Faktur. PDF do pobrania z panelu.'],
            ] as $f)
                <div class="dp-card hover:-translate-y-1 transition-transform duration-300 ease-dp">
                    <div class="text-3xl mb-3" aria-hidden="true">{{ $f['icon'] }}</div>
                    <h3 class="font-display font-semibold text-lg">{{ $f['title'] }}</h3>
                    <p class="text-sm text-dp-muted mt-2 leading-relaxed">{{ $f['body'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- PRICING TEASER + CTA BOTTOM --}}
    <section class="bg-dp-gradient text-white py-16 dp-reveal">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h2 class="font-display text-3xl sm:text-4xl font-bold">{{ __('messages.landing.cta_bottom_h2') }}</h2>
            <p class="mt-3 text-white/85 max-w-xl mx-auto">{{ __('messages.landing.cta_bottom_lead') }}</p>
            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <a href="{{ route('public.pricing') }}" class="dp-btn px-7 py-3 bg-white text-dp-purple-700 hover:shadow-dp-card-lg hover:-translate-y-0.5 transition">
                    {{ __('messages.landing.cta_bottom_primary') }}
                </a>
                <a href="{{ route('public.faq') }}" class="dp-btn px-4 py-3 ring-1 ring-white/40 text-white hover:bg-white/10">{{ __('messages.nav.faq') }}</a>
            </div>
        </div>
    </section>

    {{-- FAQ teaser --}}
    <section class="max-w-3xl mx-auto px-4 py-16" x-data="{ open: null }">
        <h2 class="text-center font-display text-3xl font-bold mb-10">{{ __('messages.landing.faq_h2') }}</h2>
        <div class="space-y-3">
            @foreach ($faqItems as $i => $item)
                <div class="dp-card !p-0 overflow-hidden">
                    <button type="button" @click="open = (open === {{ $i }} ? null : {{ $i }})"
                            class="w-full text-left px-6 py-4 flex items-center justify-between hover:bg-dp-purple-50/50 transition">
                        <span class="font-display font-semibold">{{ $item['q'] }}</span>
                        <span class="text-dp-purple-500 transition-transform" :class="open === {{ $i }} ? 'rotate-45' : ''">+</span>
                    </button>
                    <div x-show="open === {{ $i }}" x-cloak x-collapse>
                        <div class="px-6 pb-5 text-sm text-dp-muted leading-relaxed">{{ $item['a'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="text-center mt-8">
            <a href="{{ route('public.faq') }}" class="text-dp-purple-700 font-semibold hover:underline">{{ __('messages.landing.faq_more') }}</a>
        </p>
    </section>
@endsection
