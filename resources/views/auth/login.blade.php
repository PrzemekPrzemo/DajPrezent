@extends('layouts.panel')

@section('title', __('messages.auth.login_h1'))

@section('content')
    <div class="max-w-md mx-auto mt-8">
        <div class="dp-card">
            <header class="text-center mb-6">
                <h1 class="font-display text-3xl font-bold m-0">{{ __('messages.auth.login_h1') }}</h1>
                <p class="text-sm text-dp-muted mt-2 m-0">
                    Wpisz adres e-mail i hasło, których użyłeś przy rejestracji.
                </p>
            </header>

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div class="dp-field">
                    <label class="dp-label" for="email">{{ __('messages.auth.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           required autofocus autocomplete="username" class="dp-input">
                </div>

                <div class="dp-field">
                    <label class="dp-label" for="password">{{ __('messages.auth.password') }}</label>
                    <input id="password" type="password" name="password"
                           required autocomplete="current-password" class="dp-input">
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" value="1"
                               class="rounded border-gray-300 text-dp-purple-600 focus:ring-dp-purple-500">
                        <span class="text-sm text-dp-navy">{{ __('messages.auth.remember') }}</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm text-dp-purple-700 hover:underline">
                        {{ __('messages.auth.forgot_password') }}
                    </a>
                </div>

                <button type="submit" class="dp-btn-primary w-full py-3 text-base mt-2">
                    {{ __('messages.auth.login_submit') }} →
                </button>

                <p class="text-center text-sm text-dp-muted mt-4">
                    {{ __('messages.auth.no_account') }}
                    <a href="{{ route('register') }}" class="text-dp-purple-700 font-semibold hover:underline">
                        {{ __('messages.auth.register_h1') }} →
                    </a>
                </p>
            </form>
        </div>
    </div>
@endsection
