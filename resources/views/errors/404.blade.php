@extends('layouts.public')

@section('title', 'Nie znaleziono strony')
@section('robots')<meta name="robots" content="noindex,nofollow">@endsection

@section('content')
    <div class="card">
        <h1>404 — nic tu nie ma</h1>
        <p>Strony pod tym adresem nie ma — może została usunięta albo nigdy nie istniała.</p>
        <p style="margin-top:1.5rem;">
            <a href="{{ route('home') }}" class="button" style="background:#ec4899;color:#fff;text-decoration:none;padding:.65rem 1.25rem;border-radius:.5rem;font-weight:600;">← Strona główna</a>
        </p>
    </div>
@endsection
