@extends('layouts.panel')

@section('title', 'Zakup pakietu '.$package->name)

@section('content')
    <h1>Zakup pakietu: {{ $package->name }}</h1>
    <p style="color:#6b7280;">
        Cena: <strong>{{ number_format($package->price_pln_gr / 100, 2, ',', ' ') }} zł brutto</strong>
        @if ($package->price_pln_gr > 0) · ważność: {{ $package->valid_days }} dni @endif
    </p>

    <div class="card">
        <form method="POST" action="{{ route('public.checkout.store', ['code' => $package->code]) }}" x-data="{ company: {{ old('is_company') ? 'true' : 'false' }} }">
            @csrf

            <h2 style="margin-top:0;">Lista</h2>
            <div class="field">
                <label for="name">Nazwa listy*</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="120" placeholder="np. Lista Ani na 30. urodziny">
            </div>
            <div class="field">
                <label for="slug">Adres listy*</label>
                <div style="display:flex;align-items:stretch;gap:0;">
                    <span style="padding:.5rem .75rem;background:#f3f4f6;border:1px solid #d1d5db;border-right:0;border-radius:.375rem 0 0 .375rem;color:#6b7280;font-size:.95rem;">dajprezent.pl/</span>
                    <input id="slug" type="text" name="slug" value="{{ old('slug') }}" required maxlength="40" pattern="[a-z0-9][a-z0-9-]{0,38}[a-z0-9]" style="border-radius:0 .375rem .375rem 0;flex:1;">
                </div>
                <p style="color:#6b7280;font-size:.8rem;margin:.25rem 0 0;">Małe litery, cyfry, myślniki. Zarezerwowane słowa (admin, login, …) są zabronione.</p>
            </div>
            <div class="field">
                <label for="locale">Język listy</label>
                <select id="locale" name="locale">
                    <option value="pl" @selected(old('locale', 'pl') === 'pl')>Polski</option>
                    <option value="en" @selected(old('locale') === 'en')>English</option>
                </select>
            </div>

            <h2 style="margin-top:1.5rem;">Dane do faktury</h2>
            <p style="color:#6b7280;font-size:.85rem;margin-top:0;">
                Faktura VAT zostanie wystawiona automatycznie i wysłana do KSeF. Wymagane przy każdym zakupie — dla osób fizycznych jest to faktura imienna.
            </p>

            <div class="field">
                <label for="buyer_name">Imię i nazwisko / Pełna nazwa*</label>
                <input id="buyer_name" type="text" name="buyer_name" value="{{ old('buyer_name', auth()->user()?->name) }}" required maxlength="120">
            </div>
            <div class="field">
                <label for="buyer_street">Ulica i numer*</label>
                <input id="buyer_street" type="text" name="buyer_street" value="{{ old('buyer_street') }}" required maxlength="150" placeholder="np. Kwiatowa 12/4">
            </div>
            <div class="field" style="display:grid;grid-template-columns:140px 1fr;gap:.75rem;">
                <div>
                    <label for="buyer_postal_code">Kod pocztowy*</label>
                    <input id="buyer_postal_code" type="text" name="buyer_postal_code" value="{{ old('buyer_postal_code') }}" required pattern="\d{2}-\d{3}" placeholder="00-000">
                </div>
                <div>
                    <label for="buyer_city">Miejscowość*</label>
                    <input id="buyer_city" type="text" name="buyer_city" value="{{ old('buyer_city') }}" required maxlength="80">
                </div>
            </div>

            <div class="field" style="display:flex;align-items:center;gap:.5rem;margin-top:.75rem;">
                <input id="is_company" type="checkbox" name="is_company" value="1" x-model="company" @checked(old('is_company'))>
                <label for="is_company" style="margin:0;font-weight:400;">Kupuję na firmę (chcę FV z NIP-em)</label>
            </div>

            <template x-if="company">
                <div>
                    <div class="field">
                        <label for="buyer_company">Nazwa firmy*</label>
                        <input id="buyer_company" type="text" name="buyer_company" value="{{ old('buyer_company') }}" maxlength="160">
                    </div>
                    <div class="field">
                        <label for="buyer_nip">NIP*</label>
                        <input id="buyer_nip" type="text" name="buyer_nip" value="{{ old('buyer_nip') }}" maxlength="13" placeholder="1234567890">
                        <p style="color:#6b7280;font-size:.8rem;margin:.25rem 0 0;">10 cyfr (z separatorami lub bez).</p>
                    </div>
                </div>
            </template>

            <div class="field" style="display:flex;align-items:flex-start;gap:.5rem;margin-top:1rem;">
                <input id="terms" type="checkbox" name="terms" value="1" required style="margin-top:.25rem;">
                <label for="terms" style="margin:0;font-weight:400;font-size:.85rem;line-height:1.5;">
                    Akceptuję <a href="{{ route('public.legal.terms') }}" target="_blank">regulamin</a> oraz wyrażam zgodę na rozpoczęcie świadczenia usługi przed upływem terminu odstąpienia od umowy.
                </label>
            </div>
            <div class="field" style="display:flex;justify-content:space-between;align-items:center;">
                <a href="{{ route('public.pricing') }}" class="btn btn-secondary">← Zmień pakiet</a>
                <button type="submit">
                    @if ($package->price_pln_gr === 0)
                        Załóż listę
                    @else
                        Przejdź do PayU
                    @endif
                </button>
            </div>
        </form>
    </div>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection
