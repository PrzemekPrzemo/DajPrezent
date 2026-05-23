@extends('layouts.panel')

@section('title', 'Moje konto')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
        <h1 style="margin:0;">Moje konto</h1>
        <a href="{{ route('owner.dashboard') }}" class="btn btn-secondary">← Panel</a>
    </div>

    @if (! $user->hasVerifiedEmail())
        <div class="flash" style="background:#fef3c7;color:#92400e;margin-bottom:1rem;">
            Twój adres e-mail jest niepotwierdzony.
            <form method="POST" action="{{ route('verification.send') }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn" style="background:transparent;color:#92400e;text-decoration:underline;padding:0;font-weight:600;">Wyślij ponownie</button>
            </form>
        </div>
    @endif

    <div class="card">
        <h2>Profil</h2>
        <form method="POST" action="{{ route('owner.account.profile.update') }}">
            @csrf
            @method('PATCH')
            <div class="field">
                <label for="name">Imię</label>
                <input id="name" type="text" name="name" maxlength="80" required value="{{ old('name', $user->name) }}">
            </div>
            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" type="email" name="email" required value="{{ old('email', $user->email) }}">
                <p style="color:#6b7280;font-size:.8rem;margin:.25rem 0 0;">Zmiana e-maila wymaga ponownej weryfikacji.</p>
            </div>
            <div class="field" style="text-align:right;">
                <button type="submit">Zapisz profil</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Twoje dane (RODO)</h2>
        <p style="color:#6b7280;font-size:.9rem;margin-top:0;">
            Zgodnie z art. 20 RODO masz prawo do otrzymania kopii swoich danych. Eksport zawiera Twoje konto,
            wszystkie listy, prezenty (również usunięte) i faktury. <strong>Nie zawiera</strong> adresów e-mail
            gości, którzy rezerwowali Twoje prezenty — DajPrezent.pl jest dla tych danych osobnym administratorem.
        </p>
        <a href="{{ route('owner.rodo.export') }}" class="btn">⬇ Pobierz moje dane (JSON)</a>
    </div>

    <div class="card" style="border:1px solid #fecaca;background:#fff5f5;">
        <h2 style="margin-top:0;color:#991b1b;">Usuń konto (RODO art. 17)</h2>
        <p style="color:#7f1d1d;font-size:.9rem;">
            Usunięcie konta <strong>nieodwracalnie</strong> zamyka wszystkie Twoje listy, kasuje zdjęcia prezentów
            i adresy e-mail gości, którzy rezerwowali. Faktury zostają zanonimizowane i przechowywane przez 5 lat
            (wymóg ustawy o rachunkowości). Po wykonaniu zostaniesz wylogowany.
        </p>
        <form method="POST" action="{{ route('owner.account.destroy') }}"
              onsubmit="return confirm('NA PEWNO usunąć konto {{ $user->email }}? Tej operacji NIE da się cofnąć.');">
            @csrf
            @method('DELETE')
            <div class="field">
                <label for="confirm_email" style="color:#7f1d1d;">Wpisz swój adres e-mail aby potwierdzić</label>
                <input id="confirm_email" type="email" name="confirm_email" required autocomplete="off">
            </div>
            <div class="field">
                <label for="del_current_password" style="color:#7f1d1d;">Aktualne hasło</label>
                <input id="del_current_password" type="password" name="current_password" required autocomplete="current-password">
            </div>
            <div style="text-align:right;">
                <button type="submit" class="btn btn-danger">Usuń konto nieodwracalnie</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Hasło</h2>
        <form method="POST" action="{{ route('owner.account.password.update') }}">
            @csrf
            @method('PATCH')
            <div class="field">
                <label for="current_password">Aktualne hasło</label>
                <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="field">
                <label for="password">Nowe hasło (min. 8 znaków)</label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
            </div>
            <div class="field">
                <label for="password_confirmation">Powtórz nowe hasło</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
            <div class="field" style="text-align:right;">
                <button type="submit">Zmień hasło</button>
            </div>
        </form>
    </div>
@endsection
