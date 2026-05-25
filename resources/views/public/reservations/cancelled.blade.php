@extends('layouts.public')

@section('title', __('messages.reservations_status.cancelled_h1'))

@section('content')
    <div class="max-w-md mx-auto mt-20 mb-10 px-4 text-center">
        <div class="text-7xl mb-4">↩️</div>
        <h1 class="font-display text-3xl sm:text-4xl font-bold m-0">{{ __('messages.reservations_status.cancelled_h1') }}</h1>
        <p class="text-dp-muted mt-3">{{ __('messages.reservations_status.cancelled_lead') }}</p>
    </div>
@endsection
