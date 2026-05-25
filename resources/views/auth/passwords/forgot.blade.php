@extends('layouts.panel')

@section('title', __('messages.auth.forgot_h1'))

@section('content')
    <div class="max-w-md mx-auto mt-8">
        <div class="dp-card">
            <header class="text-center mb-6">
                <h1 class="font-display text-3xl font-bold m-0">{{ __('messages.auth.forgot_h1') }}</h1>
                <p class="text-sm text-dp-muted mt-2 m-0">{{ __('messages.auth.forgot_lead') }}</p>
            </header>

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div class="dp-field">
                    <label class="dp-label" for="email">{{ __('messages.auth.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           required autofocus class="dp-input">
                </div>

                <button type="submit" class="dp-btn-primary w-full py-3 text-base">
                    {{ __('messages.auth.forgot_submit') }} →
                </button>

                <p class="text-center text-sm text-dp-muted mt-4">
                    <a href="{{ route('login') }}" class="text-dp-purple-700 font-semibold hover:underline">
                        {{ __('messages.auth.reset_back_to_login') }}
                    </a>
                </p>
            </form>
        </div>
    </div>
@endsection
