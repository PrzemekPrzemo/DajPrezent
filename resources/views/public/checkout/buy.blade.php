@extends('layouts.panel')

@section('title', 'Zakup pakietu '.$package->name)

@section('content')
    <header class="max-w-3xl mx-auto mb-6">
        <a href="{{ route('public.pricing') }}" class="text-sm text-dp-muted hover:text-dp-purple-700">← Zmień pakiet</a>
        <h1 class="font-display text-3xl sm:text-4xl font-bold m-0 mt-2">Zakup pakietu {{ $package->name }}</h1>
        <p class="text-dp-muted mt-2">
            Cena: <strong class="text-dp-navy">{{ number_format($package->price_pln_gr / 100, 2, ',', ' ') }} zł brutto</strong>
            @if ($package->price_pln_gr > 0) · ważność: <strong class="text-dp-navy">{{ $package->valid_days }} dni</strong> @endif
        </p>
    </header>

    <form method="POST" action="{{ route('public.checkout.store', ['code' => $package->code]) }}"
          x-data="{ company: {{ old('is_company') ? 'true' : 'false' }} }"
          class="max-w-3xl mx-auto grid lg:grid-cols-[1fr,320px] gap-6 items-start">
        @csrf

        <div class="space-y-6">
            {{-- LIST DETAILS --}}
            <div class="dp-card">
                <h2 class="font-display font-semibold text-lg m-0 mb-4">Twoja lista</h2>

                <div class="dp-field">
                    <label class="dp-label" for="name">Nazwa listy<span class="text-dp-pink">*</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}"
                           required maxlength="120" class="dp-input"
                           placeholder="np. Lista Ani na 30. urodziny">
                </div>

                <div class="dp-field">
                    <label class="dp-label" for="slug">Adres listy<span class="text-dp-pink">*</span></label>
                    <div class="flex items-stretch gap-0">
                        <span class="px-3 py-2 bg-dp-purple-50/60 border border-r-0 border-gray-200 rounded-l-dp text-sm text-dp-muted">
                            dajprezent.pl/
                        </span>
                        <input id="slug" type="text" name="slug" value="{{ old('slug') }}"
                               required maxlength="40" pattern="[a-z0-9][a-z0-9-]{0,38}[a-z0-9]"
                               class="dp-input !rounded-l-none flex-1"
                               placeholder="ania-urodziny">
                    </div>
                    <p class="text-xs text-dp-muted mt-1">
                        Małe litery, cyfry, myślniki. Zarezerwowane słowa (admin, login…) są zabronione.
                    </p>
                </div>

                <div class="dp-field">
                    <label class="dp-label" for="locale">Język listy</label>
                    <select id="locale" name="locale" class="dp-input">
                        <option value="pl" @selected(old('locale', 'pl') === 'pl')>Polski</option>
                        <option value="en" @selected(old('locale') === 'en')>English</option>
                    </select>
                </div>
            </div>

            {{-- BILLING --}}
            <div class="dp-card">
                <h2 class="font-display font-semibold text-lg m-0">Dane do faktury</h2>
                <p class="text-sm text-dp-muted mt-1 mb-4">
                    Faktura VAT wystawiana automatycznie i wysyłana do KSeF. Dla osób fizycznych — faktura imienna.
                </p>

                <div class="dp-field">
                    <label class="dp-label" for="buyer_name">Imię i nazwisko / Pełna nazwa<span class="text-dp-pink">*</span></label>
                    <input id="buyer_name" type="text" name="buyer_name"
                           value="{{ old('buyer_name', auth()->user()?->name) }}"
                           required maxlength="120" class="dp-input">
                </div>

                <div class="dp-field">
                    <label class="dp-label" for="buyer_street">Ulica i numer<span class="text-dp-pink">*</span></label>
                    <input id="buyer_street" type="text" name="buyer_street"
                           value="{{ old('buyer_street') }}"
                           required maxlength="150" class="dp-input"
                           placeholder="np. Kwiatowa 12/4">
                </div>

                <div class="grid grid-cols-[140px_1fr] gap-3">
                    <div class="dp-field">
                        <label class="dp-label" for="buyer_postal_code">Kod<span class="text-dp-pink">*</span></label>
                        <input id="buyer_postal_code" type="text" name="buyer_postal_code"
                               value="{{ old('buyer_postal_code') }}"
                               required pattern="\d{2}-\d{3}" class="dp-input" placeholder="00-000">
                    </div>
                    <div class="dp-field">
                        <label class="dp-label" for="buyer_city">Miejscowość<span class="text-dp-pink">*</span></label>
                        <input id="buyer_city" type="text" name="buyer_city"
                               value="{{ old('buyer_city') }}"
                               required maxlength="80" class="dp-input">
                    </div>
                </div>

                <label class="flex items-center gap-2 mt-4 cursor-pointer select-none">
                    <input id="is_company" type="checkbox" name="is_company" value="1"
                           x-model="company" @checked(old('is_company'))
                           class="rounded border-gray-300 text-dp-purple-600 focus:ring-dp-purple-500">
                    <span class="text-sm text-dp-navy">Kupuję na firmę (chcę FV z NIP-em)</span>
                </label>

                <template x-if="company">
                    <div class="mt-3 space-y-3 p-4 rounded-dp bg-dp-purple-50/40 border border-dp-purple-100">
                        <div class="dp-field !mt-0">
                            <label class="dp-label" for="buyer_company">Nazwa firmy<span class="text-dp-pink">*</span></label>
                            <input id="buyer_company" type="text" name="buyer_company"
                                   value="{{ old('buyer_company') }}" maxlength="160" class="dp-input">
                        </div>
                        <div class="dp-field">
                            <label class="dp-label" for="buyer_nip">NIP<span class="text-dp-pink">*</span></label>
                            <input id="buyer_nip" type="text" name="buyer_nip"
                                   value="{{ old('buyer_nip') }}" maxlength="13" class="dp-input"
                                   placeholder="1234567890">
                            <p class="text-xs text-dp-muted mt-1">10 cyfr (z separatorami lub bez).</p>
                        </div>
                    </div>
                </template>
            </div>

            {{-- TERMS + SUBMIT --}}
            <div class="dp-card">
                <label class="flex items-start gap-2 cursor-pointer">
                    <input id="terms" type="checkbox" name="terms" value="1" required
                           class="mt-1 rounded border-gray-300 text-dp-purple-600 focus:ring-dp-purple-500">
                    <span class="text-sm text-dp-navy leading-relaxed">
                        Akceptuję <a href="{{ route('public.legal.terms') }}" target="_blank" class="text-dp-purple-700 hover:underline">regulamin</a>
                        oraz wyrażam zgodę na rozpoczęcie świadczenia usługi przed upływem terminu odstąpienia od umowy.
                    </span>
                </label>

                <button type="submit" class="dp-btn-primary w-full mt-4 py-3 text-base">
                    @if ($package->price_pln_gr === 0)
                        ✨ Załóż listę za darmo
                    @else
                        💳 Przejdź do PayU →
                    @endif
                </button>
                <p class="text-xs text-dp-muted mt-3 text-center">
                    🔒 Płatność realizowana przez PayU. Po pomyślnej transakcji dostaniesz fakturę VAT na maila.
                </p>
            </div>
        </div>

        {{-- SUMMARY SIDEBAR --}}
        <aside class="dp-card lg:sticky lg:top-20 self-start">
            <h3 class="font-display font-semibold text-base m-0 mb-3">Podsumowanie</h3>
            <div class="flex items-center justify-between text-sm border-b border-dp-purple-50 pb-3 mb-3">
                <span class="text-dp-muted">Pakiet</span>
                <strong>{{ $package->name }}</strong>
            </div>
            <div class="flex items-center justify-between text-sm border-b border-dp-purple-50 pb-3 mb-3">
                <span class="text-dp-muted">Limit prezentów</span>
                <strong>{{ $package->gift_limit === null ? '∞' : $package->gift_limit }}</strong>
            </div>
            <div class="flex items-center justify-between text-sm border-b border-dp-purple-50 pb-3 mb-3">
                <span class="text-dp-muted">Ważność</span>
                <strong>{{ $package->valid_days }} dni</strong>
            </div>
            <div class="flex items-center justify-between mt-4">
                <span class="text-dp-muted">Do zapłaty</span>
                <span class="font-display text-2xl font-bold bg-dp-gradient bg-clip-text text-transparent">
                    {{ number_format($package->price_pln_gr / 100, 2, ',', ' ') }} zł
                </span>
            </div>
            <ul class="mt-4 space-y-1.5 text-xs text-dp-muted">
                <li class="flex items-center gap-1.5"><span class="text-dp-green">✓</span> Faktura VAT w cenie</li>
                <li class="flex items-center gap-1.5"><span class="text-dp-green">✓</span> Bez automatycznych odnowień</li>
                <li class="flex items-center gap-1.5"><span class="text-dp-green">✓</span> Bezpieczna płatność PayU</li>
            </ul>
        </aside>
    </form>
@endsection
