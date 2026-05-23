@extends('layouts.panel')

@section('title', 'RSVP: '.$tenant->name)

@section('content')
    <header class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display text-2xl sm:text-3xl font-bold m-0">Potwierdzenia obecności</h1>
            <p class="text-sm text-dp-muted mt-1 m-0">{{ $tenant->name }} — {{ $stats['head_count'] }} osób potwierdziło, {{ $stats['declined'] }} odmówiło.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('owner.wedding.exports.rsvps-csv', $tenant) }}" class="dp-btn-secondary">⬇ CSV (catering)</a>
            <a href="{{ route('owner.wedding.exports.invitation-pdf', $tenant) }}" class="dp-btn-secondary">📄 Zaproszenie PDF</a>
            <a href="{{ route('owner.wedding.edit', $tenant) }}" class="dp-btn-ghost">← Strona ślubna</a>
        </div>
    </header>

    {{-- STATS BAND --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @foreach ([
            ['v' => $stats['total'],         'l' => 'odpowiedzi'],
            ['v' => $stats['attending'],     'l' => 'potwierdziło'],
            ['v' => $stats['declined'],      'l' => 'odmówiło'],
            ['v' => $stats['head_count'],    'l' => 'miejsc razem'],
            ['v' => $stats['with_dietary'],  'l' => 'z dietą'],
            ['v' => $stats['with_transport'],'l' => 'transport'],
        ] as $s)
            <div class="dp-card !p-4 text-center">
                <div class="font-display text-2xl font-bold bg-dp-gradient bg-clip-text text-transparent">{{ $s['v'] }}</div>
                <div class="text-xs text-dp-muted mt-0.5">{{ $s['l'] }}</div>
            </div>
        @endforeach
    </div>

    @if ($rsvps->isEmpty())
        <div class="dp-card text-center py-10">
            <div class="text-3xl mb-2" aria-hidden="true">💌</div>
            <h3 class="font-display font-semibold text-lg mb-1">Brak potwierdzeń — jeszcze.</h3>
            <p class="text-sm text-dp-muted">Goście wypełnią formularz na <a href="/{{ $tenant->slug }}" class="text-dp-purple-700 hover:underline">dajprezent.pl/{{ $tenant->slug }}</a>. Możesz pobrać zaproszenie PDF z QR.</p>
        </div>
    @else
        <div class="dp-card">
            <div class="overflow-x-auto -mx-6 px-6">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase tracking-wide text-dp-muted border-b border-slate-100">
                        <tr>
                            <th class="text-left py-2 px-2">Imię i nazwisko</th>
                            <th class="text-left py-2 px-2">E-mail</th>
                            <th class="text-center py-2 px-2">Obecność</th>
                            <th class="text-center py-2 px-2">+1</th>
                            <th class="text-left py-2 px-2">Dieta</th>
                            <th class="text-left py-2 px-2">Wiadomość</th>
                            <th class="text-right py-2 px-2">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rsvps as $r)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="py-3 px-2 font-semibold">{{ $r->guest_name }}</td>
                                <td class="py-3 px-2 text-dp-muted">{{ $r->guest_email ?? '—' }}</td>
                                <td class="py-3 px-2 text-center">
                                    @if ($r->attending)
                                        <span class="dp-chip dp-chip-received">tak</span>
                                    @else
                                        <span class="dp-chip" style="background:#fee2e2;color:#991b1b;">nie</span>
                                    @endif
                                </td>
                                <td class="py-3 px-2 text-center">
                                    {{ $r->plus_one ? '✓ '.($r->plus_one_name ?? '+1') : '—' }}
                                </td>
                                <td class="py-3 px-2 text-xs">{{ $r->dietary ?? '—' }}</td>
                                <td class="py-3 px-2 text-xs">{{ $r->message ? \Illuminate\Support\Str::limit($r->message, 60) : '—' }}</td>
                                <td class="py-3 px-2 text-right text-xs text-dp-muted whitespace-nowrap">{{ $r->created_at?->format('d.m.Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $rsvps->links() }}</div>
    @endif
@endsection
