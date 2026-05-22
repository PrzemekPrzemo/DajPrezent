@extends('layouts.panel')

@section('title', 'Moje faktury')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;flex-wrap:wrap;gap:.5rem;">
        <h1 style="margin:0;">Moje faktury</h1>
        <a href="{{ route('owner.dashboard') }}" class="btn btn-secondary">← Panel</a>
    </div>

    @if ($invoices->isEmpty())
        <div class="card"><p style="color:#6b7280;">Nie masz jeszcze żadnych faktur. Faktury powstają automatycznie po opłaceniu pakietu.</p></div>
    @else
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Numer</th>
                        <th>Lista</th>
                        <th>Kwota</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>KSeF</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($invoices as $inv)
                    <tr>
                        <td><strong>{{ $inv->number }}</strong></td>
                        <td>
                            <a href="/{{ $inv->tenant?->slug }}" target="_blank">
                                {{ $inv->tenant?->name ?? '—' }}
                            </a>
                        </td>
                        <td>{{ number_format($inv->total_gross_gr / 100, 2, ',', ' ') }} zł</td>
                        <td>{{ $inv->created_at?->format('d.m.Y') }}</td>
                        <td>
                            @switch($inv->status)
                                @case('sent') <span class="chip chip-received">wysłana</span> @break
                                @case('accepted') <span class="chip chip-received">zaakceptowana</span> @break
                                @case('queued') <span class="chip chip-reserved">w kolejce</span> @break
                                @case('rejected') <span class="chip" style="background:#fee2e2;color:#991b1b;">odrzucona</span> @break
                                @default <span class="chip chip-available">{{ $inv->status }}</span>
                            @endswitch
                        </td>
                        <td style="font-family:monospace;font-size:.8rem;">
                            {{ $inv->ksef_reference_number ?? '—' }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem;">{{ $invoices->links() }}</div>
    @endif

    <div class="card" style="background:#fff7ed;color:#92400e;font-size:.9rem;">
        Potrzebujesz faktury z innym NIP-em lub kopii e-FV (XML KSeF)? Napisz na
        <a href="mailto:faktury@dajprezent.pl">faktury@dajprezent.pl</a> — odpowiemy w ciągu 1 dnia roboczego.
    </div>
@endsection
