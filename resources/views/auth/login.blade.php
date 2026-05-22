@extends('layouts.panel')

@section('title', 'Zaloguj się')

@section('content')
    <div style="max-width: 420px; margin: 3rem auto 0;">
        <div class="card">
            <h1>Zaloguj się do panelu</h1>
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="field">
                    <label for="email">E-mail</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                </div>
                <div class="field">
                    <label for="password">Hasło</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                </div>
                <div class="field" style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                    <label style="margin:0;font-weight:400;display:flex;align-items:center;gap:.4rem;">
                        <input type="checkbox" name="remember" value="1"> Zapamiętaj mnie
                    </label>
                    <a href="{{ route('password.request') }}" style="font-size:.85rem;">Zapomniałem hasła</a>
                </div>
                <div class="field" style="display:flex;gap:.5rem;justify-content:space-between;align-items:center;">
                    <a href="{{ route('register') }}" style="font-size:.9rem;">Nie mam jeszcze konta</a>
                    <button type="submit">Zaloguj</button>
                </div>
            </form>
        </div>
    </div>
@endsection
