@extends('layouts.public')

@section('title', 'Brak dostępu')
@section('robots')<meta name="robots" content="noindex,nofollow">@endsection

@section('content')
    <div class="card">
        <h1>403 — brak dostępu</h1>
        <p>Nie masz uprawnień do tej strony.</p>
        <p style="margin-top:1.5rem;">
            <a href="{{ route('home') }}" class="button" style="background:#ec4899;color:#fff;text-decoration:none;padding:.65rem 1.25rem;border-radius:.5rem;font-weight:600;">← Strona główna</a>
        </p>
    </div>
@endsection
