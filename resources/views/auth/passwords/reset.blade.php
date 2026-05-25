@extends('layouts.panel')

@section('title', __('messages.auth.reset_token_h1'))

@section('content')
    <div class="max-w-md mx-auto mt-8">
        <div class="dp-card">
            <header class="text-center mb-6">
                <h1 class="font-display text-3xl font-bold m-0">{{ __('messages.auth.reset_token_h1') }}</h1>
            </header>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="dp-field">
                    <label class="dp-label" for="email">{{ __('messages.auth.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}"
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

                <button type="submit" class="dp-btn-primary w-full py-3 text-base mt-2">
                    {{ __('messages.auth.reset_token_submit') }}
                </button>
            </form>
        </div>
    </div>
@endsection
