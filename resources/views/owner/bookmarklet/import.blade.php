@extends('layouts.panel')

@section('title', 'Dodaj prezent ze sklepu')

@section('content')
    <h1>Dodaj prezent</h1>
    <p style="color:#6b7280;">Sprawdź wykryte dane, wybierz listę i kliknij „Dodaj prezent". Po zapisaniu możesz zamknąć to okno i wrócić do sklepu.</p>

    @if ($tenants->isEmpty())
        <div class="flash flash-err">
            Nie masz jeszcze listy, do której można dodać prezent. Załóż jedną w panelu i spróbuj ponownie.
        </div>
    @else
        <div class="card">
            <form method="POST" action="{{ route('owner.bookmarklet.store') }}">
                @csrf
                <div class="field">
                    <label for="tenant_id">Lista*</label>
                    <select id="tenant_id" name="tenant_id" required>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                {{ $tenant->name }} (dajprezent.pl/{{ $tenant->slug }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="title">Tytuł*</label>
                    <input id="title" type="text" name="title" maxlength="120" required value="{{ old('title', $title) }}">
                </div>
                <div class="field">
                    <label for="url">Link do sklepu</label>
                    <input id="url" type="url" name="url" maxlength="1024" value="{{ old('url', $url) }}">
                </div>
                <div class="field" style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                    <div>
                        <label for="price_pln">Cena (zł)</label>
                        <input id="price_pln" type="number" step="0.01" min="0" name="price_pln" value="{{ old('price_pln', $price) }}">
                    </div>
                    <div>
                        <label for="priority">Priorytet</label>
                        <select id="priority" name="priority">
                            <option value="1" @selected(old('priority') == 1)>1 — muszę mieć</option>
                            <option value="2" @selected(old('priority', 2) == 2)>2 — normalny</option>
                            <option value="3" @selected(old('priority') == 3)>3 — nice to have</option>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label for="description">Opis (opcjonalnie)</label>
                    <textarea id="description" name="description" rows="2" maxlength="2000">{{ old('description') }}</textarea>
                </div>
                <div class="field" style="display:flex;justify-content:space-between;align-items:center;">
                    <button type="button" class="btn btn-secondary" onclick="window.close();">Zamknij</button>
                    <button type="submit">Dodaj prezent</button>
                </div>
            </form>
        </div>
    @endif
@endsection
