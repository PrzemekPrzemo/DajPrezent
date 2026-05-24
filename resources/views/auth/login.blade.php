@extends('layouts.panel')

@section('title', __('messages.auth.login_h1'))

@section('content')
    <div style="max-width: 420px; margin: 3rem auto 0;">
        <div class="card">
            <h1>{{ __('messages.auth.login_h1') }}</h1>
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="field">
                    <label for="email">{{ __('messages.auth.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                </div>
                <div class="field">
                    <label for="password">{{ __('messages.auth.password') }}</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                </div>
                <div class="field" style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                    <label style="margin:0;font-weight:400;display:flex;align-items:center;gap:.4rem;">
                        <input type="checkbox" name="remember" value="1"> {{ __('messages.auth.remember') }}
                    </label>
                    <a href="{{ route('password.request') }}" style="font-size:.85rem;">{{ __('messages.auth.forgot_password') }}</a>
                </div>
                <div class="field" style="display:flex;gap:.5rem;justify-content:space-between;align-items:center;">
                    <a href="{{ route('register') }}" style="font-size:.9rem;">{{ __('messages.auth.no_account') }}</a>
                    <button type="submit">{{ __('messages.auth.login_submit') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
