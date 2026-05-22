@extends('layouts.panel')

@section('title', 'Ustawienia: '.$tenant->name)

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
        <h1 style="margin:0;">Ustawienia listy</h1>
        <a href="{{ route('owner.gifts.index', $tenant) }}" class="btn btn-secondary">← Prezenty</a>
    </div>
    <p style="color:#6b7280;margin-top:0;">dajprezent.pl/{{ $tenant->slug }}</p>

    <div class="card">
        <form method="POST" action="{{ route('owner.tenant.settings.update', $tenant) }}">
            @csrf
            @method('PATCH')
            <div class="field">
                <label for="name">Nazwa listy</label>
                <input id="name" type="text" name="name" maxlength="120" value="{{ old('name', $tenant->name) }}" required>
            </div>

            <fieldset style="border:1px solid #e5e7eb;padding:1rem;border-radius:.5rem;margin-top:1rem;">
                <legend style="padding:0 .5rem;font-weight:600;">Hasło dostępu (opcjonalne)</legend>
                <p style="color:#6b7280;font-size:.85rem;margin-top:0;">
                    @if ($tenant->isPasswordProtected())
                        Lista <strong>jest</strong> chroniona hasłem. Wpisz nowe hasło, aby je zmienić, lub zaznacz „Usuń hasło".
                    @else
                        Lista <strong>nie jest</strong> chroniona. Wpisanie hasła ustawi ochronę i odbiorcy będą musieli go podać przy wejściu.
                    @endif
                </p>
                <div class="field">
                    <label for="password">Nowe hasło (min. 4 znaki)</label>
                    <input id="password" type="password" name="password" maxlength="128" autocomplete="new-password">
                </div>
                @if ($tenant->isPasswordProtected())
                    <div class="field" style="display:flex;align-items:center;gap:.5rem;">
                        <input id="remove_password" type="checkbox" name="remove_password" value="1">
                        <label for="remove_password" style="margin:0;font-weight:400;">Usuń hasło — lista znów będzie publiczna.</label>
                    </div>
                @endif
            </fieldset>

            <div class="field" style="text-align:right;margin-top:1rem;">
                <button type="submit">Zapisz</button>
            </div>
        </form>
    </div>
@endsection
