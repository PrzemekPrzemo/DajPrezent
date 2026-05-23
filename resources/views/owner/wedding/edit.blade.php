@extends('layouts.panel')

@section('title', 'Strona ślubna: '.$tenant->name)

@section('content')
    <header class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display text-2xl sm:text-3xl font-bold m-0">Strona ślubna</h1>
            <p class="text-sm text-dp-muted mt-1 m-0">
                Publiczny adres:
                <a href="/{{ $tenant->slug }}" target="_blank" class="text-dp-purple-700 hover:underline">dajprezent.pl/{{ $tenant->slug }}</a>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('owner.wedding.rsvps.index', $tenant) }}" class="dp-btn-secondary">💌 RSVP gości</a>
            <a href="{{ route('owner.wedding.exports.invitation-pdf', $tenant) }}" class="dp-btn-secondary">📄 Zaproszenie PDF</a>
            <a href="{{ route('owner.gifts.index', $tenant) }}" class="dp-btn-secondary">Prezenty →</a>
            <a href="{{ route('owner.dashboard') }}" class="dp-btn-ghost">← Panel</a>
        </div>
    </header>

    <form method="POST" action="{{ route('owner.wedding.update', $tenant) }}">
        @csrf
        @method('PATCH')

        <div class="dp-card">
            <h2 class="font-display font-semibold text-lg m-0 mb-3">Para młoda</h2>
            <div class="dp-field">
                <label for="couple_names" class="dp-label">Imiona pary</label>
                <input id="couple_names" type="text" name="couple_names"
                       value="{{ old('couple_names', $event->couple_names) }}"
                       maxlength="120" class="dp-input" placeholder="Anna & Tomek">
            </div>
            <div class="dp-field">
                <label for="hashtag" class="dp-label">Hashtag (opcjonalnie)</label>
                <input id="hashtag" type="text" name="hashtag"
                       value="{{ old('hashtag', $event->hashtag) }}"
                       maxlength="60" class="dp-input" placeholder="#AnnaITomek2026">
            </div>
            <div class="dp-field">
                <label for="story_text" class="dp-label">Nasza historia (opcjonalnie)</label>
                <textarea id="story_text" name="story_text" rows="4" maxlength="5000"
                          class="dp-input" placeholder="Cześć! Poznaliśmy się…">{{ old('story_text', $event->story_text) }}</textarea>
            </div>
        </div>

        <div class="dp-card">
            <h2 class="font-display font-semibold text-lg m-0 mb-3">Ceremonia</h2>
            <div class="dp-field grid sm:grid-cols-2 gap-3">
                <div>
                    <label for="ceremony_at" class="dp-label">Data i godzina ceremonii</label>
                    <input id="ceremony_at" type="datetime-local" name="ceremony_at"
                           value="{{ old('ceremony_at', $event->ceremony_at?->format('Y-m-d\TH:i')) }}"
                           class="dp-input">
                </div>
                <div>
                    <label for="dress_code" class="dp-label">Dress code (opcjonalnie)</label>
                    <input id="dress_code" type="text" name="dress_code"
                           value="{{ old('dress_code', $event->dress_code) }}"
                           maxlength="80" class="dp-input" placeholder="cocktail attire">
                </div>
            </div>
            <div class="dp-field">
                <label for="venue_name" class="dp-label">Miejsce ceremonii</label>
                <input id="venue_name" type="text" name="venue_name"
                       value="{{ old('venue_name', $event->venue_name) }}"
                       maxlength="160" class="dp-input" placeholder="np. Kościół św. Jana">
            </div>
            <div class="dp-field">
                <label for="venue_address" class="dp-label">Adres ceremonii</label>
                <input id="venue_address" type="text" name="venue_address"
                       value="{{ old('venue_address', $event->venue_address) }}"
                       maxlength="255" class="dp-input" placeholder="ul. Świętojańska 1, 00-001 Warszawa">
            </div>
            <div class="dp-field grid sm:grid-cols-2 gap-3">
                <div>
                    <label for="venue_lat" class="dp-label">Szerokość geograficzna (opcjonalnie)</label>
                    <input id="venue_lat" type="number" step="0.000001" name="venue_lat"
                           value="{{ old('venue_lat', $event->venue_lat) }}" class="dp-input">
                </div>
                <div>
                    <label for="venue_lng" class="dp-label">Długość geograficzna (opcjonalnie)</label>
                    <input id="venue_lng" type="number" step="0.000001" name="venue_lng"
                           value="{{ old('venue_lng', $event->venue_lng) }}" class="dp-input">
                </div>
            </div>
        </div>

        <div class="dp-card">
            <h2 class="font-display font-semibold text-lg m-0 mb-3">Wesele / Przyjęcie</h2>
            <div class="dp-field">
                <label for="reception_venue_name" class="dp-label">Sala weselna</label>
                <input id="reception_venue_name" type="text" name="reception_venue_name"
                       value="{{ old('reception_venue_name', $event->reception_venue_name) }}"
                       maxlength="160" class="dp-input">
            </div>
            <div class="dp-field">
                <label for="reception_venue_address" class="dp-label">Adres sali</label>
                <input id="reception_venue_address" type="text" name="reception_venue_address"
                       value="{{ old('reception_venue_address', $event->reception_venue_address) }}"
                       maxlength="255" class="dp-input">
            </div>
            <div class="dp-field">
                <label for="schedule_text" class="dp-label">Harmonogram dnia</label>
                <textarea id="schedule_text" name="schedule_text" rows="6" maxlength="5000"
                          class="dp-input" placeholder="16:00 — ceremonia
17:30 — sesja zdjęciowa
18:30 — przyjęcie weselne">{{ old('schedule_text', $event->schedule_text) }}</textarea>
            </div>
            <div class="dp-field">
                <label for="accommodation_text" class="dp-label">Noclegi w okolicy (opcjonalnie)</label>
                <textarea id="accommodation_text" name="accommodation_text" rows="4" maxlength="5000"
                          class="dp-input">{{ old('accommodation_text', $event->accommodation_text) }}</textarea>
            </div>
        </div>

        <div class="dp-card">
            <h2 class="font-display font-semibold text-lg m-0 mb-3">RSVP i motyw</h2>
            <div class="dp-field grid sm:grid-cols-2 gap-3">
                <div>
                    <label for="rsvp_deadline" class="dp-label">Termin potwierdzenia obecności</label>
                    <input id="rsvp_deadline" type="date" name="rsvp_deadline"
                           value="{{ old('rsvp_deadline', $event->rsvp_deadline?->format('Y-m-d')) }}"
                           class="dp-input">
                </div>
                <div>
                    <label for="theme" class="dp-label">Motyw strony</label>
                    <select id="theme" name="theme" class="dp-input">
                        @foreach (App\Domain\Wedding\Models\WeddingEvent::THEMES as $themeOption)
                            <option value="{{ $themeOption }}" @selected(old('theme', $event->theme) === $themeOption)>
                                {{ ucfirst($themeOption) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-4">
            <button type="submit" class="dp-btn-primary px-6">Zapisz stronę ślubną</button>
        </div>
    </form>
@endsection
