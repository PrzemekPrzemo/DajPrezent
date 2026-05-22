@extends('layouts.panel')

@section('title', 'Moje listy')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <h1 style="margin:0;">Moje listy prezentów</h1>
        <div style="display:flex;gap:.5rem;">
            <a href="{{ route('owner.invoices.index') }}" class="btn btn-secondary">Faktury</a>
            <a href="{{ route('owner.bookmarklet.show') }}" class="btn btn-secondary">⚡ Bookmarklet</a>
        </div>
    </div>

    @if ($tenants->isEmpty())
        <div class="card">
            <p>Nie masz jeszcze żadnej listy. <a href="{{ route('public.pricing') }}">Wybierz pakiet</a>, aby założyć pierwszą.</p>
        </div>
    @else
        @foreach ($tenants as $tenant)
            @php
                $total = (int) $tenant->gifts_total;
                $taken = (int) $tenant->gifts_reserved + (int) $tenant->gifts_received;
                $progress = $total > 0 ? (int) round($taken * 100 / $total) : 0;

                // Carbon's diff sign convention is finicky across versions —
                // compute days via UNIX timestamps so the math is unambiguous.
                $daysLeft = $tenant->expires_at
                    ? (int) floor(($tenant->expires_at->timestamp - now()->timestamp) / 86400)
                    : null;
                $expiryClass = match (true) {
                    $daysLeft === null => 'expiry-none',
                    $daysLeft < 0 => 'expiry-expired',
                    $daysLeft < 14 => 'expiry-soon',
                    $daysLeft < 30 => 'expiry-warning',
                    default => 'expiry-ok',
                };
            @endphp
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
                    <div style="flex:1;min-width:240px;">
                        <h2 style="margin-bottom:.25rem;">{{ $tenant->name }}</h2>
                        <div style="color:#6b7280;font-size:.9rem;">
                            <a href="/{{ $tenant->slug }}" target="_blank">dajprezent.pl/{{ $tenant->slug }}</a>
                            @if ($tenant->expires_at)
                                ·
                                <span class="chip {{ $expiryClass }}" style="font-size:.75rem;padding:.1rem .5rem;">
                                    @if ($daysLeft === null)
                                        bez terminu
                                    @elseif ($daysLeft < 0)
                                        wygasła {{ abs($daysLeft) }} dni temu
                                    @elseif ($daysLeft === 0)
                                        wygasa dziś
                                    @elseif ($daysLeft < 14)
                                        wygasa za {{ $daysLeft }} dni
                                    @else
                                        ważna do {{ $tenant->expires_at->translatedFormat('j F Y') }}
                                    @endif
                                </span>
                            @endif
                        </div>

                        <div style="margin-top:.75rem;display:flex;gap:.4rem;flex-wrap:wrap;">
                            <span class="chip chip-available">{{ $total }} prezentów</span>
                            <span class="chip chip-reserved">{{ $tenant->gifts_reserved }} zarezerwowanych</span>
                            <span class="chip chip-received">{{ $tenant->gifts_received }} otrzymanych</span>
                        </div>

                        @if ($total > 0)
                            <div style="margin-top:.75rem;">
                                <div style="background:#f3f4f6;border-radius:9999px;overflow:hidden;height:.5rem;">
                                    <div style="width:{{ $progress }}%;height:100%;background:#ec4899;transition:width .3s;"></div>
                                </div>
                                <div style="color:#6b7280;font-size:.75rem;margin-top:.25rem;">{{ $progress }}% prezentów ma odbiorcę</div>
                            </div>
                        @endif
                    </div>

                    <div style="display:flex;flex-direction:column;gap:.4rem;">
                        <a href="{{ route('owner.gifts.index', $tenant) }}" class="btn">Zarządzaj prezentami</a>
                        <a href="{{ route('owner.tenant.settings.edit', $tenant) }}" class="btn btn-secondary" style="text-align:center;">Ustawienia</a>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    <style>
        .chip.expiry-ok { background:#ecfdf5;color:#065f46; }
        .chip.expiry-warning { background:#fef3c7;color:#92400e; }
        .chip.expiry-soon { background:#fee2e2;color:#991b1b; }
        .chip.expiry-expired { background:#374151;color:#fff; }
        .chip.expiry-none { background:#f3f4f6;color:#6b7280; }
    </style>
@endsection
