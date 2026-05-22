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
                <div class="field" style="display:flex;align-items:center;gap:.5rem;">
                    <input id="remember" type="checkbox" name="remember" value="1">
                    <label for="remember" style="margin:0;font-weight:400;">Zapamiętaj mnie</label>
                </div>
                <div class="field" style="display:flex;gap:.5rem;justify-content:flex-end;align-items:center;">
                    <a href="{{ route('home') }}" class="btn btn-secondary">Strona główna</a>
                    <button type="submit">Zaloguj</button>
                </div>
            </form>
        </div>
    </div>
@endsection
