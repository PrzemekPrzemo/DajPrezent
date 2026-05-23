@extends('layouts.public')

@section('title', $event?->couple_names ?: $tenant->name)
@section('meta_description', ($event?->couple_names ?: $tenant->name).' — RSVP, ceremonia i lista prezentów.')
@section('og_title', ($event?->couple_names ?: $tenant->name).' — strona ślubna')
@section('og_image', route('public.og.tenant', ['tenant' => $tenant->slug]))
@section('robots')<meta name="robots" content="noindex,follow">@endsection

@if ($event !== null)
    @push('head_extra')
        <x-seo.jsonld :data="\App\Domain\Seo\JsonLd::weddingEvent($event, url()->current())"/>
    @endpush
@endif

@php
    // Per-theme color overlays. Wszystkie używają tych samych komponentów
    // dp-*, ale heroes i akcenty dostają inny gradient z dokumentu UX.
    $themeStyle = match ($event?->theme ?? 'classic') {
        'minimalist' => ['hero' => 'bg-white text-dp-navy ring-1 ring-slate-200', 'accent' => 'text-dp-purple-700'],
        'garden'     => ['hero' => 'bg-gradient-to-br from-emerald-100 to-rose-100 text-emerald-900', 'accent' => 'text-emerald-700'],
        'gold'       => ['hero' => 'bg-gradient-to-br from-slate-900 to-slate-700 text-amber-100', 'accent' => 'text-amber-400'],
        default      => ['hero' => 'bg-dp-gradient text-white', 'accent' => 'text-dp-purple-700'],
    };
@endphp

@section('content')
    {{-- HERO --}}
    <section class="{{ $themeStyle['hero'] }} rounded-dp-lg p-8 sm:p-12 text-center mb-8 shadow-dp-card">
        @if ($event?->hashtag)
            <span class="inline-block text-xs uppercase tracking-wider opacity-80 mb-2">{{ $event->hashtag }}</span>
        @endif
        <h1 class="font-display text-3xl sm:text-5xl font-bold m-0">{{ $event?->couple_names ?: $tenant->name }}</h1>
        @if ($event?->ceremony_at)
            <p class="mt-3 text-lg opacity-90 m-0">
                {{ $event->ceremony_at->translatedFormat('j F Y, H:i') }}
            </p>
        @endif
        @if ($event?->venue_name)
            <p class="mt-1 opacity-80 m-0">{{ $event->venue_name }}</p>
        @endif
    </section>

    @if (session('rsvp_status'))
        <div role="status" class="bg-emerald-50 text-emerald-800 rounded-dp px-4 py-3 text-sm mb-6">{{ session('rsvp_status') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="bg-red-50 text-red-800 rounded-dp px-4 py-3 text-sm mb-6">
            @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
        </div>
    @endif

    @if ($event?->story_text)
        <section class="dp-card mb-4">
            <h2 class="font-display font-semibold text-xl m-0 mb-2">Nasza historia</h2>
            <p class="text-dp-navy whitespace-pre-line leading-relaxed m-0">{{ $event->story_text }}</p>
        </section>
    @endif

    {{-- CEREMONY DETAILS + MAP LINK --}}
    @if ($event?->venue_name || $event?->reception_venue_name)
        <section class="dp-card mb-4">
            <h2 class="font-display font-semibold text-xl m-0 mb-3">Gdzie i kiedy</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                @if ($event->venue_name)
                    <div>
                        <p class="text-xs uppercase tracking-wider text-dp-muted m-0">Ceremonia</p>
                        <p class="font-semibold m-0 mt-1">{{ $event->venue_name }}</p>
                        @if ($event->venue_address)
                            <p class="text-sm text-dp-muted m-0">{{ $event->venue_address }}</p>
                        @endif
                        @if ($event->venue_lat && $event->venue_lng)
                            <a href="https://www.google.com/maps?q={{ $event->venue_lat }},{{ $event->venue_lng }}"
                               target="_blank" rel="noopener"
                               class="text-sm {{ $themeStyle['accent'] }} hover:underline">📍 Otwórz w Google Maps</a>
                        @elseif ($event->venue_address)
                            <a href="https://www.google.com/maps?q={{ urlencode($event->venue_address) }}"
                               target="_blank" rel="noopener"
                               class="text-sm {{ $themeStyle['accent'] }} hover:underline">📍 Otwórz w Google Maps</a>
                        @endif
                    </div>
                @endif
                @if ($event->reception_venue_name)
                    <div>
                        <p class="text-xs uppercase tracking-wider text-dp-muted m-0">Wesele</p>
                        <p class="font-semibold m-0 mt-1">{{ $event->reception_venue_name }}</p>
                        @if ($event->reception_venue_address)
                            <p class="text-sm text-dp-muted m-0">{{ $event->reception_venue_address }}</p>
                        @endif
                    </div>
                @endif
            </div>
            @if ($event->dress_code)
                <p class="mt-4 text-sm text-dp-muted m-0">Dress code: <strong>{{ $event->dress_code }}</strong></p>
            @endif
        </section>
    @endif

    @if ($event?->schedule_text)
        <section class="dp-card mb-4">
            <h2 class="font-display font-semibold text-xl m-0 mb-2">Harmonogram</h2>
            <p class="text-dp-navy whitespace-pre-line leading-relaxed m-0">{{ $event->schedule_text }}</p>
        </section>
    @endif

    @if ($event?->accommodation_text)
        <section class="dp-card mb-4">
            <h2 class="font-display font-semibold text-xl m-0 mb-2">Noclegi w okolicy</h2>
            <p class="text-dp-navy whitespace-pre-line leading-relaxed m-0">{{ $event->accommodation_text }}</p>
        </section>
    @endif

    {{-- RSVP --}}
    <section class="dp-card mb-4" x-data="{ attending: '1', plusOne: false }">
        <h2 class="font-display font-semibold text-xl m-0 mb-2">Potwierdź obecność (RSVP)</h2>
        @if ($event?->rsvp_deadline)
            <p class="text-sm text-dp-muted m-0 mb-4">
                Prosimy o potwierdzenie do <strong>{{ $event->rsvp_deadline->translatedFormat('j F Y') }}</strong>.
                @if ($event->rsvp_deadline->isPast())
                    <span class="text-red-700 font-semibold block mt-1">Termin minął — skontaktuj się bezpośrednio z parą.</span>
                @endif
            </p>
        @endif

        <form method="POST" action="/{{ $tenant->slug }}/rsvp">
            @csrf
            <div class="dp-field grid sm:grid-cols-2 gap-3">
                <div>
                    <label for="rsvp_name" class="dp-label">Imię i nazwisko*</label>
                    <input id="rsvp_name" type="text" name="guest_name" required maxlength="120" class="dp-input">
                </div>
                <div>
                    <label for="rsvp_email" class="dp-label">E-mail (opcjonalnie)</label>
                    <input id="rsvp_email" type="email" name="guest_email" maxlength="255" class="dp-input">
                </div>
            </div>

            <fieldset class="dp-field border-0 p-0">
                <legend class="dp-label">Czy będziesz?</legend>
                <label class="inline-flex items-center gap-2 text-sm mr-4">
                    <input type="radio" name="attending" value="1" x-model="attending" checked> Tak, będę
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="radio" name="attending" value="0" x-model="attending"> Nie dam rady
                </label>
            </fieldset>

            <template x-if="attending === '1'">
                <div>
                    <div class="dp-field flex items-center gap-2">
                        <input id="rsvp_plusone" type="checkbox" name="plus_one" value="1" x-model="plusOne">
                        <label for="rsvp_plusone" class="m-0 text-sm">Przyjdę z osobą towarzyszącą</label>
                    </div>
                    <template x-if="plusOne">
                        <div class="dp-field">
                            <label for="rsvp_po_name" class="dp-label">Imię osoby towarzyszącej</label>
                            <input id="rsvp_po_name" type="text" name="plus_one_name" maxlength="120" class="dp-input">
                        </div>
                    </template>
                    @if ($tenant->kind === 'wedding_premium')
                        <div class="dp-field">
                            <label for="rsvp_dietary" class="dp-label">Preferencje dietetyczne / alergie</label>
                            <input id="rsvp_dietary" type="text" name="dietary" maxlength="200"
                                   placeholder="np. wegetariańska, bez orzechów" class="dp-input">
                        </div>
                        <div class="dp-field flex items-center gap-2">
                            <input id="rsvp_transport" type="checkbox" name="transport_needed" value="1">
                            <label for="rsvp_transport" class="m-0 text-sm">Potrzebuję transportu</label>
                        </div>
                    @endif
                </div>
            </template>

            <div class="dp-field">
                <label for="rsvp_message" class="dp-label">Wiadomość dla pary (opcjonalnie)</label>
                <textarea id="rsvp_message" name="message" rows="3" maxlength="1000" class="dp-input"></textarea>
            </div>

            <div class="flex justify-end mt-4">
                <button type="submit" class="dp-btn-primary px-6">Wyślij RSVP</button>
            </div>
        </form>
    </section>

    {{-- GIFTS at the bottom of the wedding page --}}
    @if (! $gifts->isEmpty())
        <section>
            <h2 class="text-center font-display text-2xl font-bold mb-2">Lista prezentów</h2>
            <p class="text-center text-dp-muted text-sm mb-6">
                Jeśli chcesz nam coś podarować, kliknij i zarezerwuj — bliscy nie widzą siebie nawzajem.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" x-data="{open:null}">
                @foreach ($gifts as $gift)
                    @include('public.wedding.partials.gift-card', ['gift' => $gift, 'tenant' => $tenant])
                @endforeach
            </div>
        </section>
    @endif
@endsection
