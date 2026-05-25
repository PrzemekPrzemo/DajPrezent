@extends('layouts.panel')

@section('title', 'Dodaj prezent z bookmarkletu')

@section('content')
    <div class="max-w-2xl mx-auto">
        <header class="text-center mb-6">
            <div class="w-14 h-14 mx-auto mb-3 rounded-dp-lg bg-dp-gradient flex items-center justify-center text-white text-2xl shadow-dp-card">
                🔖
            </div>
            <h1 class="font-display text-2xl sm:text-3xl font-bold m-0">Dodaj prezent ze sklepu</h1>
            <p class="text-sm text-dp-muted mt-2 max-w-md mx-auto">
                Sprawdź wykryte dane, wybierz listę i kliknij „Dodaj prezent".
                Po zapisaniu możesz zamknąć to okno i wrócić do sklepu.
            </p>
        </header>

        @if ($errors->any())
            <div class="dp-flash-err mb-4">
                @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
            </div>
        @endif

        @if ($tenants->isEmpty())
            <div class="dp-card text-center py-10">
                <div class="text-5xl mb-3">📋</div>
                <h2 class="font-display font-semibold text-lg m-0">Nie masz jeszcze listy</h2>
                <p class="text-sm text-dp-muted mt-2 mb-5">
                    Załóż listę w panelu i wróć tutaj — bookmarklet wrzuci prezent w jeden klik.
                </p>
                <a href="{{ route('public.pricing') }}" class="dp-btn-primary px-6 py-2.5">
                    Wybierz pakiet →
                </a>
            </div>
        @else
            <form method="POST" action="{{ route('owner.bookmarklet.store') }}" class="dp-card space-y-4">
                @csrf

                {{-- AUTO-DETECTED preview (jeśli coś wpadło z bookmarkletu) --}}
                @if ($title || $url)
                    <div class="-mx-6 -mt-6 px-6 py-3 bg-emerald-50/60 border-b border-emerald-100 rounded-t-dp-lg">
                        <p class="text-sm text-emerald-900 m-0">
                            ✓ Wykryto dane ze strony.
                            @if (parse_url($url, PHP_URL_HOST))
                                Źródło: <strong>{{ parse_url($url, PHP_URL_HOST) }}</strong>.
                            @endif
                            Możesz je edytować przed zapisem.
                        </p>
                    </div>
                @endif

                <div class="dp-field">
                    <label class="dp-label" for="tenant_id">Lista<span class="text-dp-pink">*</span></label>
                    <select id="tenant_id" name="tenant_id" required class="dp-input">
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                {{ $tenant->name }} (dajprezent.pl/{{ $tenant->slug }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="dp-field">
                    <label class="dp-label" for="title">Tytuł<span class="text-dp-pink">*</span></label>
                    <input id="title" type="text" name="title" maxlength="120" required
                           value="{{ old('title', $title) }}" class="dp-input"
                           placeholder="np. Aparat Instax mini 12">
                </div>

                <div class="dp-field">
                    <label class="dp-label" for="url">Link do sklepu</label>
                    <input id="url" type="url" name="url" maxlength="1024"
                           value="{{ old('url', $url) }}" class="dp-input"
                           placeholder="https://...">
                </div>

                <div class="grid sm:grid-cols-2 gap-3">
                    <div class="dp-field">
                        <label class="dp-label" for="price_pln">Cena (zł)</label>
                        <input id="price_pln" type="number" step="0.01" min="0" name="price_pln"
                               value="{{ old('price_pln', $price) }}" class="dp-input"
                               placeholder="np. 299.00">
                    </div>
                    <div class="dp-field">
                        <label class="dp-label" for="priority">Priorytet</label>
                        <select id="priority" name="priority" class="dp-input">
                            <option value="1" @selected(old('priority') == 1)>1 — muszę mieć</option>
                            <option value="2" @selected(old('priority', 2) == 2)>2 — normalny</option>
                            <option value="3" @selected(old('priority') == 3)>3 — nice to have</option>
                        </select>
                    </div>
                </div>

                <div class="dp-field">
                    <label class="dp-label" for="description">Notatka (opcjonalnie)</label>
                    <textarea id="description" name="description" rows="2" maxlength="2000"
                              class="dp-input"
                              placeholder="np. rozmiar L, kolor butelkowa zieleń">{{ old('description') }}</textarea>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100 -mx-6 px-6 pt-4">
                    <button type="button" onclick="window.close();" class="dp-btn-ghost">
                        ← Zamknij
                    </button>
                    <button type="submit" class="dp-btn-primary px-6 py-2.5">
                        ✨ Dodaj prezent
                    </button>
                </div>
            </form>

            <p class="text-center text-xs text-dp-muted mt-4">
                Okno zostanie zamknięte automatycznie po dodaniu prezentu.
            </p>
        @endif
    </div>
@endsection
