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
            <p>Nie masz jeszcze żadnej listy. Pakiet aktywuje się automatycznie po opłaceniu — strona zakupu w przygotowaniu.</p>
        </div>
    @else
        @foreach ($tenants as $tenant)
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                    <div>
                        <h2 style="margin-bottom:.25rem;">{{ $tenant->name }}</h2>
                        <div style="color:#6b7280;font-size:.9rem;">
                            <a href="/{{ $tenant->slug }}" target="_blank">dajprezent.pl/{{ $tenant->slug }}</a>
                            @if ($tenant->expires_at)
                                · ważna do {{ $tenant->expires_at->translatedFormat('j F Y') }}
                            @endif
                        </div>
                        <div style="font-size:.85rem;color:#6b7280;margin-top:.5rem;">
                            {{ $tenant->gifts_total }} prezentów ·
                            {{ $tenant->gifts_reserved }} zarezerwowanych ·
                            {{ $tenant->gifts_received }} otrzymanych
                        </div>
                    </div>
                    <a href="{{ route('owner.gifts.index', $tenant) }}" class="btn">Zarządzaj prezentami</a>
                </div>
            </div>
        @endforeach
    @endif
@endsection
