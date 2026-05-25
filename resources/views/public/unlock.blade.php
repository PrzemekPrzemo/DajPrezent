@extends('layouts.public')

@section('title', $tenant->name.' — '.__('messages.unlock.h1'))
@section('robots')<meta name="robots" content="noindex,nofollow">@endsection

@section('content')
    <div class="max-w-md mx-auto mt-12 mb-10 px-4">
        <div class="dp-card text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-dp-lg bg-dp-gradient flex items-center justify-center text-white text-2xl shadow-dp-card">
                🔒
            </div>
            <h1 class="font-display text-2xl font-bold m-0">{{ $tenant->name }}</h1>
            <p class="text-sm text-dp-muted mt-2">{{ __('messages.unlock.lead') }}</p>

            @if ($errors->any())
                <div role="alert" class="dp-flash-err mt-4 text-left">
                    {{ $errors->first('password') }}
                </div>
            @endif

            <form method="POST" action="/{{ $tenant->slug }}/unlock" class="text-left mt-5 space-y-3">
                @csrf
                <div class="dp-field">
                    <label class="dp-label" for="password">{{ __('messages.unlock.password') }}</label>
                    <input id="password" type="password" name="password" required autofocus maxlength="128"
                           class="dp-input" autocomplete="current-password">
                </div>
                <button type="submit" class="dp-btn-primary w-full py-3">
                    {{ __('messages.unlock.submit') }} →
                </button>
            </form>
        </div>
    </div>
@endsection
