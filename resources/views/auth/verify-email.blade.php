@extends('layouts.panel')

@section('title', 'Potwierdź adres e-mail')

@section('content')
    <div style="max-width:480px;margin:3rem auto 0;">
        <div class="card" style="text-align:center;">
            <h1>Potwierdź swój adres e-mail</h1>
            <p style="color:#6b7280;">Wysłaliśmy link aktywacyjny na <strong>{{ auth()->user()?->email }}</strong>. Kliknij w niego, aby zakończyć rejestrację.</p>
            <p style="color:#6b7280;font-size:.9rem;">Nie dostałeś maila? Sprawdź folder Spam.</p>

            <form method="POST" action="{{ route('verification.send') }}" style="margin-top:1rem;">
                @csrf
                <button type="submit">Wyślij ponownie</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" style="margin-top:.5rem;">
                @csrf
                <button type="submit" class="btn-secondary" style="background:#e5e7eb;color:#111827;border:0;padding:.5rem 1rem;border-radius:.5rem;font-weight:600;cursor:pointer;">
                    Wyloguj się
                </button>
            </form>
        </div>
    </div>
@endsection
