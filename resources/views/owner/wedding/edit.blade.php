@extends('layouts.panel')

@section('title', __('messages.wedding_owner.page_title', ['name' => $tenant->name]))

@section('content')
    <header class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display text-2xl sm:text-3xl font-bold m-0">{{ __('messages.wedding_owner.h1') }}</h1>
            <p class="text-sm text-dp-muted mt-1 m-0">
                {{ __('messages.wedding_owner.public_address') }}:
                <a href="/{{ $tenant->slug }}" target="_blank" class="text-dp-purple-700 hover:underline">dajprezent.pl/{{ $tenant->slug }}</a>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('owner.wedding.rsvps.index', $tenant) }}" class="dp-btn-secondary">{{ __('messages.wedding_owner.btn_rsvps') }}</a>
            <a href="{{ route('owner.wedding.exports.invitation-pdf', $tenant) }}" class="dp-btn-secondary">{{ __('messages.wedding_owner.btn_invitation_pdf') }}</a>
            <a href="{{ route('owner.gifts.index', $tenant) }}" class="dp-btn-secondary">{{ __('messages.wedding_owner.btn_gifts') }}</a>
            <a href="{{ route('owner.dashboard') }}" class="dp-btn-ghost">{{ __('messages.wedding_owner.btn_panel_back') }}</a>
        </div>
    </header>

    <form method="POST" action="{{ route('owner.wedding.update', $tenant) }}">
        @csrf
        @method('PATCH')

        <div class="dp-card">
            <h2 class="font-display font-semibold text-lg m-0 mb-3">{{ __('messages.wedding_owner.section_couple') }}</h2>
            <div class="dp-field">
                <label for="couple_names" class="dp-label">{{ __('messages.wedding_owner.couple_names') }}</label>
                <input id="couple_names" type="text" name="couple_names"
                       value="{{ old('couple_names', $event->couple_names) }}"
                       maxlength="120" class="dp-input" placeholder="Anna & Tomek">
            </div>
            <div class="dp-field">
                <label for="hashtag" class="dp-label">{{ __('messages.wedding_owner.hashtag') }}</label>
                <input id="hashtag" type="text" name="hashtag"
                       value="{{ old('hashtag', $event->hashtag) }}"
                       maxlength="60" class="dp-input" placeholder="#AnnaITomek2026">
            </div>
            <div class="dp-field">
                <label for="story_text" class="dp-label">{{ __('messages.wedding_owner.story') }}</label>
                <textarea id="story_text" name="story_text" rows="4" maxlength="5000"
                          class="dp-input" placeholder="{{ __('messages.wedding_owner.story_placeholder') }}">{{ old('story_text', $event->story_text) }}</textarea>
            </div>
        </div>

        <div class="dp-card">
            <h2 class="font-display font-semibold text-lg m-0 mb-3">{{ __('messages.wedding_owner.section_ceremony') }}</h2>
            <div class="dp-field grid sm:grid-cols-2 gap-3">
                <div>
                    <label for="ceremony_at" class="dp-label">{{ __('messages.wedding_owner.ceremony_at') }}</label>
                    <input id="ceremony_at" type="datetime-local" name="ceremony_at"
                           value="{{ old('ceremony_at', $event->ceremony_at?->format('Y-m-d\TH:i')) }}"
                           class="dp-input">
                </div>
                <div>
                    <label for="dress_code" class="dp-label">{{ __('messages.wedding_owner.dress_code') }}</label>
                    <input id="dress_code" type="text" name="dress_code"
                           value="{{ old('dress_code', $event->dress_code) }}"
                           maxlength="80" class="dp-input" placeholder="cocktail attire">
                </div>
            </div>
            <div class="dp-field">
                <label for="venue_name" class="dp-label">{{ __('messages.wedding_owner.venue_name') }}</label>
                <input id="venue_name" type="text" name="venue_name"
                       value="{{ old('venue_name', $event->venue_name) }}"
                       maxlength="160" class="dp-input" placeholder="{{ __('messages.wedding_owner.venue_name_placeholder') }}">
            </div>
            <div class="dp-field">
                <label for="venue_address" class="dp-label">{{ __('messages.wedding_owner.venue_address') }}</label>
                <input id="venue_address" type="text" name="venue_address"
                       value="{{ old('venue_address', $event->venue_address) }}"
                       maxlength="255" class="dp-input" placeholder="ul. Świętojańska 1, 00-001 Warszawa">
            </div>
            <div class="dp-field grid sm:grid-cols-2 gap-3">
                <div>
                    <label for="venue_lat" class="dp-label">{{ __('messages.wedding_owner.venue_lat') }}</label>
                    <input id="venue_lat" type="number" step="0.000001" name="venue_lat"
                           value="{{ old('venue_lat', $event->venue_lat) }}" class="dp-input">
                </div>
                <div>
                    <label for="venue_lng" class="dp-label">{{ __('messages.wedding_owner.venue_lng') }}</label>
                    <input id="venue_lng" type="number" step="0.000001" name="venue_lng"
                           value="{{ old('venue_lng', $event->venue_lng) }}" class="dp-input">
                </div>
            </div>
        </div>

        <div class="dp-card">
            <h2 class="font-display font-semibold text-lg m-0 mb-3">{{ __('messages.wedding_owner.section_reception') }}</h2>
            <div class="dp-field">
                <label for="reception_venue_name" class="dp-label">{{ __('messages.wedding_owner.reception_venue_name') }}</label>
                <input id="reception_venue_name" type="text" name="reception_venue_name"
                       value="{{ old('reception_venue_name', $event->reception_venue_name) }}"
                       maxlength="160" class="dp-input">
            </div>
            <div class="dp-field">
                <label for="reception_venue_address" class="dp-label">{{ __('messages.wedding_owner.reception_venue_address') }}</label>
                <input id="reception_venue_address" type="text" name="reception_venue_address"
                       value="{{ old('reception_venue_address', $event->reception_venue_address) }}"
                       maxlength="255" class="dp-input">
            </div>
            <div class="dp-field">
                <label for="schedule_text" class="dp-label">{{ __('messages.wedding_owner.schedule') }}</label>
                <textarea id="schedule_text" name="schedule_text" rows="6" maxlength="5000"
                          class="dp-input" placeholder="{{ __('messages.wedding_owner.schedule_placeholder') }}">{{ old('schedule_text', $event->schedule_text) }}</textarea>
            </div>
            <div class="dp-field">
                <label for="accommodation_text" class="dp-label">{{ __('messages.wedding_owner.accommodation') }}</label>
                <textarea id="accommodation_text" name="accommodation_text" rows="4" maxlength="5000"
                          class="dp-input">{{ old('accommodation_text', $event->accommodation_text) }}</textarea>
            </div>
        </div>

        <div class="dp-card">
            <h2 class="font-display font-semibold text-lg m-0 mb-3">{{ __('messages.wedding_owner.section_rsvp_theme') }}</h2>
            <div class="dp-field grid sm:grid-cols-2 gap-3">
                <div>
                    <label for="rsvp_deadline" class="dp-label">{{ __('messages.wedding_owner.rsvp_deadline') }}</label>
                    <input id="rsvp_deadline" type="date" name="rsvp_deadline"
                           value="{{ old('rsvp_deadline', $event->rsvp_deadline?->format('Y-m-d')) }}"
                           class="dp-input">
                </div>
                <div>
                    <label for="theme" class="dp-label">{{ __('messages.wedding_owner.theme') }}</label>
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
            <button type="submit" class="dp-btn-primary px-6">{{ __('messages.wedding_owner.submit') }}</button>
        </div>
    </form>
@endsection
