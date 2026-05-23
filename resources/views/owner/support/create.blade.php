@extends('layouts.panel')

@section('title', 'Nowe zgłoszenie')

@section('content')
    <header class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="font-display text-2xl sm:text-3xl font-bold m-0">Napisz do nas</h1>
        <a href="{{ route('owner.support.index') }}" class="dp-btn-ghost">← Wszystkie zgłoszenia</a>
    </header>

    <div class="dp-card max-w-2xl">
        <p class="text-sm text-dp-muted m-0 mb-4">
            Zanim napiszesz, zerknij do <a href="{{ route('public.help.index') }}" target="_blank" class="text-dp-purple-700 hover:underline">bazy wiedzy</a> —
            może jest tam już odpowiedź. SLA odpowiedzi: <strong>1 dzień roboczy</strong>.
        </p>

        <form method="POST" action="{{ route('owner.support.store') }}">
            @csrf
            <div class="dp-field grid sm:grid-cols-2 gap-3">
                <div>
                    <label for="category" class="dp-label">Kategoria*</label>
                    <select id="category" name="category" required class="dp-input">
                        @foreach ([
                            'billing' => 'Faktury / płatności',
                            'technical' => 'Techniczne (błąd, login, panel)',
                            'rodo' => 'RODO / dane osobowe',
                            'other' => 'Inne',
                        ] as $code => $label)
                            <option value="{{ $code }}" @selected(old('category') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="priority" class="dp-label">Priorytet*</label>
                    <select id="priority" name="priority" required class="dp-input">
                        <option value="low" @selected(old('priority') === 'low')>Niski — pytanie</option>
                        <option value="normal" @selected(old('priority', 'normal') === 'normal')>Normalny</option>
                        <option value="high" @selected(old('priority') === 'high')>Wysoki — coś nie działa</option>
                    </select>
                </div>
            </div>

            @if (! auth()->user()->tenants->isEmpty())
                <div class="dp-field">
                    <label for="tenant_id" class="dp-label">Której listy dotyczy? (opcjonalnie)</label>
                    <select id="tenant_id" name="tenant_id" class="dp-input">
                        <option value="">— nie dotyczy konkretnej listy —</option>
                        @foreach (auth()->user()->tenants as $t)
                            <option value="{{ $t->id }}" @selected((int) old('tenant_id') === $t->id)>
                                {{ $t->name }} (dajprezent.pl/{{ $t->slug }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="dp-field">
                <label for="subject" class="dp-label">Temat*</label>
                <input id="subject" type="text" name="subject" required maxlength="200"
                       value="{{ old('subject') }}" class="dp-input"
                       placeholder="np. Nie mogę wgrać zdjęcia prezentu">
            </div>

            <div class="dp-field">
                <label for="body" class="dp-label">Opis*</label>
                <textarea id="body" name="body" required rows="8" maxlength="8000" class="dp-input"
                          placeholder="Opisz problem albo pytanie — im więcej szczegółów, tym szybciej pomożemy.">{{ old('body') }}</textarea>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <a href="{{ route('owner.support.index') }}" class="dp-btn-secondary">Anuluj</a>
                <button type="submit" class="dp-btn-primary px-6">Wyślij zgłoszenie</button>
            </div>
        </form>
    </div>
@endsection
