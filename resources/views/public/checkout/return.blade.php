@extends('layouts.public')

@section('title', $cancelled ? 'Płatność anulowana' : 'Dziękujemy!')

@section('content')
    <div class="card" style="margin-top:3rem;">
        @if ($cancelled)
            <h1>Płatność anulowana</h1>
            <p>Nie pobraliśmy żadnych środków. Możesz w każdej chwili wrócić do <a href="{{ route('public.pricing') }}">wyboru pakietu</a>.</p>
        @else
            <h1>Dziękujemy za zakup!</h1>
            <p>Otrzymaliśmy informację od PayU i potwierdzamy płatność w tle. Twoja lista zostanie aktywowana w ciągu minuty.</p>
            <p style="margin-top:1.5rem;">
                <a href="{{ route('owner.dashboard') }}" class="button" style="background:#ec4899;color:#fff;text-decoration:none;padding:.6rem 1.25rem;border-radius:.5rem;font-weight:600;">Przejdź do panelu</a>
            </p>
        @endif
    </div>
@endsection
