@extends('layouts.panel')

@section('title', 'Zapomniałem hasła')

@section('content')
    <div style="max-width:420px;margin:3rem auto 0;">
        <div class="card">
            <h1>Resetowanie hasła</h1>
            <p style="color:#6b7280;">Wpisz e-mail powiązany z kontem. Wyślemy link do ustawienia nowego hasła.</p>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="field">
                    <label for="email">E-mail</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="field" style="display:flex;justify-content:space-between;align-items:center;">
                    <a href="{{ route('login') }}" style="font-size:.9rem;">← Wróć do logowania</a>
                    <button type="submit">Wyślij link</button>
                </div>
            </form>
        </div>
    </div>
@endsection
