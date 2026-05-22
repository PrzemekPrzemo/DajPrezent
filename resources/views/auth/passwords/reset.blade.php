@extends('layouts.panel')

@section('title', 'Ustaw nowe hasło')

@section('content')
    <div style="max-width:420px;margin:3rem auto 0;">
        <div class="card">
            <h1>Ustaw nowe hasło</h1>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="field">
                    <label for="email">E-mail</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username">
                </div>
                <div class="field">
                    <label for="password">Nowe hasło (min. 8 znaków)</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="password_confirmation">Powtórz hasło</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
                <div class="field" style="text-align:right;">
                    <button type="submit">Zmień hasło</button>
                </div>
            </form>
        </div>
    </div>
@endsection
