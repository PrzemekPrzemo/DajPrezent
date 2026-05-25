@extends('layouts.public')

@section('title', __('messages.errors.forbidden_h1'))
@section('robots')<meta name="robots" content="noindex,nofollow">@endsection

@section('content')
    <div class="max-w-md mx-auto mt-20 mb-10 px-4 text-center">
        <div class="text-7xl mb-4">🔒</div>
        <h1 class="font-display text-3xl sm:text-4xl font-bold m-0">{{ __('messages.errors.forbidden_h1') }}</h1>
        <p class="text-dp-muted mt-3">{{ __('messages.errors.forbidden_lead') }}</p>
        <a href="{{ route('home') }}" class="dp-btn-primary mt-6 px-6 py-3 inline-flex">
            {{ __('messages.errors.go_home') }}
        </a>
    </div>
@endsection
