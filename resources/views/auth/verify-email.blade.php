@extends('layouts.panel')

@section('title', __('messages.auth.verify_h1'))

@section('content')
    <div style="max-width:480px;margin:3rem auto 0;">
        <div class="card" style="text-align:center;">
            <h1>{{ __('messages.auth.verify_h1') }}</h1>
            <p style="color:#6b7280;">{{ __('messages.auth.verify_lead') }}<br><strong>{{ auth()->user()?->email }}</strong></p>

            <form method="POST" action="{{ route('verification.send') }}" style="margin-top:1rem;">
                @csrf
                <button type="submit">{{ __('messages.auth.verify_resend') }}</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" style="margin-top:.5rem;">
                @csrf
                <button type="submit" class="btn-secondary" style="background:#e5e7eb;color:#111827;border:0;padding:.5rem 1rem;border-radius:.5rem;font-weight:600;cursor:pointer;">
                    {{ __('messages.auth.logout') }}
                </button>
            </form>
        </div>
    </div>
@endsection
