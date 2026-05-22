@extends('layouts.public')

@section('title', 'Lista wygasła')
@section('robots')<meta name="robots" content="noindex,nofollow">@endsection

@section('content')
    <div class="card">
        <h1>Ta lista już nie istnieje</h1>
        <p>Pakiet wygasł, a właściciel jeszcze nie przedłużył. Jeśli to Twoja lista — zaloguj się i przedłuż pakiet, aby ją przywrócić.</p>
        <p style="margin-top:1.5rem;">
            <a href="{{ route('login') }}" class="button" style="background:#ec4899;color:#fff;text-decoration:none;padding:.65rem 1.25rem;border-radius:.5rem;font-weight:600;">Zaloguj się</a>
        </p>
    </div>
@endsection
