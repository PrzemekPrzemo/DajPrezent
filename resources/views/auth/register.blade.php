@extends('layouts.panel')

@section('title', __('messages.auth.register_h1'))

@section('content')
    <div class="max-w-md mx-auto mt-8">
        <div class="dp-card">
            <header class="text-center mb-6">
                <h1 class="font-display text-3xl font-bold m-0">{{ __('messages.auth.register_h1') }}</h1>
                <p class="text-sm text-dp-muted mt-2 m-0">
                    Stwórz konto i zacznij dzielić się listą prezentów z bliskimi.
                </p>
            </header>

            @if ($intendedPackage)
                <div class="rounded-dp bg-dp-purple-50 text-dp-purple-700 border border-dp-purple-200 px-4 py-3 text-sm mb-5">
                    <div class="flex items-start gap-2">
                        <span class="text-lg">🎁</span>
                        <span>{{ __('messages.auth.register_with_pkg', ['pkg' => $intendedPackage]) }}</span>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="package" value="{{ $intendedPackage }}">

                <div class="dp-field">
                    <label class="dp-label" for="name">{{ __('messages.auth.name_short') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}"
                           required autofocus maxlength="80" class="dp-input">
                </div>

                <div class="dp-field">
                    <label class="dp-label" for="email">{{ __('messages.auth.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           required autocomplete="username" class="dp-input">
                </div>

                <div class="dp-field">
                    <label class="dp-label" for="password">{{ __('messages.auth.password_hint') }}</label>
                    <input id="password" type="password" name="password"
                           required autocomplete="new-password" class="dp-input">
                </div>

                <div class="dp-field">
                    <label class="dp-label" for="password_confirmation">{{ __('messages.auth.password_confirm') }}</label>
                    <input id="password_confirmation" type="password" name="password_confirmation"
                           required autocomplete="new-password" class="dp-input">
                </div>

                <label class="flex items-start gap-2 cursor-pointer pt-1">
                    <input id="terms" type="checkbox" name="terms" value="1" required
                           class="mt-0.5 rounded border-gray-300 text-dp-purple-600 focus:ring-dp-purple-500">
                    <span class="text-xs text-dp-muted leading-relaxed">
                        {{ __('messages.auth.register_terms') }}
                    </span>
                </label>

                <button type="submit" class="dp-btn-primary w-full py-3 text-base mt-2">
                    {{ __('messages.auth.register_submit') }} →
                </button>

                <p class="text-center text-sm text-dp-muted mt-4">
                    {{ __('messages.auth.have_account_already') }}
                    <a href="{{ route('login') }}" class="text-dp-purple-700 font-semibold hover:underline">
                        {{ __('messages.auth.login_h1') }} →
                    </a>
                </p>
            </form>
        </div>

        <p class="text-center text-xs text-dp-muted mt-6">
            🔒 Twoje dane są bezpieczne. Hasła trzymamy zaszyfrowane, e-mail służy tylko do logowania i powiadomień.
        </p>
    </div>
@endsection
