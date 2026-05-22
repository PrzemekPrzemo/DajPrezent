@extends('layouts.panel')

@section('title', 'Zakup pakietu '.$package->name)

@section('content')
    <h1>Zakup pakietu: {{ $package->name }}</h1>
    <p style="color:#6b7280;">
        Cena: <strong>{{ number_format($package->price_pln_gr / 100, 2, ',', ' ') }} zł brutto</strong>
        @if ($package->price_pln_gr > 0) · ważność: {{ $package->valid_days }} dni @endif
    </p>

    <div class="card">
        <form method="POST" action="{{ route('public.checkout.store', ['code' => $package->code]) }}">
            @csrf
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
            <div class="field" style="display:flex;align-items:flex-start;gap:.5rem;">
                <input id="terms" type="checkbox" name="terms" value="1" required style="margin-top:.25rem;">
                <label for="terms" style="margin:0;font-weight:400;font-size:.85rem;line-height:1.5;">
                    Akceptuję regulamin oraz wyrażam zgodę na rozpoczęcie świadczenia usługi przed upływem terminu odstąpienia od umowy.
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
@endsection
