@extends('layouts.public')

@section('title', __('messages.expired.h1'))
@section('robots')<meta name="robots" content="noindex,nofollow">@endsection

@section('content')
    <div class="max-w-md mx-auto mt-20 mb-10 px-4 text-center">
        <div class="text-7xl mb-4">⏰</div>
        <h1 class="font-display text-3xl sm:text-4xl font-bold m-0">{{ __('messages.expired.h1') }}</h1>
        <p class="text-dp-muted mt-3">{{ __('messages.expired.lead') }}</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ route('login') }}" class="dp-btn-primary px-6 py-3">{{ __('messages.nav.login') }}</a>
            <a href="{{ route('home') }}" class="dp-btn-ghost px-4 py-3">{{ __('messages.errors.go_home') }}</a>
        </div>
    </div>
@endsection
