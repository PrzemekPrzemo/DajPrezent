@extends('layouts.panel')

@section('title', 'Wsparcie')

@section('content')
    <header class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display text-2xl sm:text-3xl font-bold m-0">Wsparcie</h1>
            <p class="text-sm text-dp-muted mt-1 m-0">Odpowiadamy mailowo w ciągu 1 dnia roboczego.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('public.help.index') }}" class="dp-btn-secondary" target="_blank">📚 Baza wiedzy</a>
            <a href="{{ route('owner.support.create') }}" class="dp-btn-primary">+ Nowe zgłoszenie</a>
        </div>
    </header>

    @if (session('status'))
        <div role="status" class="bg-emerald-50 text-emerald-800 rounded-dp px-4 py-3 text-sm mb-4">{{ session('status') }}</div>
    @endif

    @if ($tickets->isEmpty())
        <div class="dp-card text-center py-10">
            <div class="text-3xl mb-2" aria-hidden="true">💬</div>
            <h3 class="font-display font-semibold text-lg mb-1">Nie masz jeszcze zgłoszeń.</h3>
            <p class="text-sm text-dp-muted max-w-md mx-auto mb-4">Zanim napiszesz, sprawdź <a href="{{ route('public.help.index') }}" class="text-dp-purple-700 hover:underline" target="_blank">bazę wiedzy</a> — najczęstsze pytania są tam już rozwiązane.</p>
            <a href="{{ route('owner.support.create') }}" class="dp-btn-primary px-6">+ Napisz do nas</a>
        </div>
    @else
        <div class="dp-card !p-0 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="text-xs uppercase tracking-wide text-dp-muted border-b border-slate-100">
                    <tr>
                        <th class="text-left py-3 px-4">#</th>
                        <th class="text-left py-3 px-4">Temat</th>
                        <th class="text-left py-3 px-4">Kategoria</th>
                        <th class="text-center py-3 px-4">Status</th>
                        <th class="text-right py-3 px-4">Zgłoszono</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($tickets as $t)
                    <tr class="border-b border-slate-50 last:border-0 hover:bg-dp-purple-50/30">
                        <td class="py-3 px-4 text-dp-muted">#{{ $t->id }}</td>
                        <td class="py-3 px-4">
                            <a href="{{ route('owner.support.show', $t) }}" class="font-semibold text-dp-navy hover:text-dp-purple-700">{{ $t->subject }}</a>
                        </td>
                        <td class="py-3 px-4 text-xs">{{ $t->category }}</td>
                        <td class="py-3 px-4 text-center">
                            @switch($t->status)
                                @case('open')        <span class="dp-chip dp-chip-reserved">otwarte</span> @break
                                @case('in_progress') <span class="dp-chip dp-chip-available">w toku</span> @break
                                @case('resolved')    <span class="dp-chip dp-chip-received">rozwiązane</span> @break
                                @case('closed')      <span class="dp-chip" style="background:#f1f5f9;color:#475569;">zamknięte</span> @break
                            @endswitch
                        </td>
                        <td class="py-3 px-4 text-right text-xs text-dp-muted">{{ $t->created_at?->format('d.m.Y H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $tickets->links() }}</div>
    @endif
@endsection
