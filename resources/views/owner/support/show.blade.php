@extends('layouts.panel')

@section('title', '#'.$ticket->id.' '.$ticket->subject)

@section('content')
    <header class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <p class="text-xs text-dp-muted m-0">Zgłoszenie #{{ $ticket->id }} · {{ $ticket->category }} · {{ $ticket->priority }}</p>
            <h1 class="font-display text-2xl sm:text-3xl font-bold m-0 mt-1">{{ $ticket->subject }}</h1>
        </div>
        <div class="flex gap-2">
            @switch($ticket->status)
                @case('open')        <span class="dp-chip dp-chip-reserved">otwarte</span> @break
                @case('in_progress') <span class="dp-chip dp-chip-available">w toku</span> @break
                @case('resolved')    <span class="dp-chip dp-chip-received">rozwiązane</span> @break
                @case('closed')      <span class="dp-chip" style="background:#f1f5f9;color:#475569;">zamknięte</span> @break
            @endswitch
            <a href="{{ route('owner.support.index') }}" class="dp-btn-ghost">← Wszystkie</a>
        </div>
    </header>

    @if (session('status'))
        <div role="status" class="bg-emerald-50 text-emerald-800 rounded-dp px-4 py-3 text-sm mb-4">{{ session('status') }}</div>
    @endif

    <div class="dp-card">
        <p class="text-xs text-dp-muted m-0">Zgłoszono {{ $ticket->created_at?->format('d.m.Y H:i') }}</p>
        <div class="mt-3 whitespace-pre-line text-dp-navy leading-relaxed">{{ $ticket->body }}</div>
    </div>

    <p class="text-xs text-dp-muted mt-4">
        Odpowiedź otrzymasz mailowo na <strong>{{ $ticket->contact_email ?? auth()->user()->email }}</strong>.
        SLA: 1 dzień roboczy.
    </p>
@endsection
