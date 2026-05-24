@extends('layouts.panel')

@section('title', __('messages.auth.register_h1'))

@section('content')
    <div style="max-width:460px;margin:3rem auto 0;">
        <div class="card">
            <h1>{{ __('messages.auth.register_h1') }}</h1>
            @if ($intendedPackage)
                <p style="background:#fef3c7;color:#92400e;padding:.5rem .75rem;border-radius:.5rem;font-size:.9rem;">
                    {{ __('messages.auth.register_with_pkg', ['pkg' => $intendedPackage]) }}
                </p>
            @endif
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <input type="hidden" name="package" value="{{ $intendedPackage }}">
                <div class="field">
                    <label for="name">{{ __('messages.auth.name_short') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus maxlength="80">
                </div>
                <div class="field">
                    <label for="email">{{ __('messages.auth.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                </div>
                <div class="field">
                    <label for="password">{{ __('messages.auth.password_hint') }}</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="password_confirmation">{{ __('messages.auth.password_confirm') }}</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
                <div class="field" style="display:flex;align-items:flex-start;gap:.5rem;">
                    <input id="terms" type="checkbox" name="terms" value="1" required style="margin-top:.25rem;">
                    <label for="terms" style="margin:0;font-weight:400;font-size:.85rem;line-height:1.5;">
                        {{ __('messages.auth.register_terms') }}
                    </label>
                </div>
                <div class="field" style="display:flex;gap:.5rem;justify-content:space-between;align-items:center;">
                    <a href="{{ route('login') }}" style="font-size:.9rem;">{{ __('messages.auth.have_account_already') }}</a>
                    <button type="submit">{{ __('messages.auth.register_submit') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
