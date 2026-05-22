@extends('layouts.public')

@section('title', 'Pakiety i ceny')

@section('content')
    <header style="text-align:center;margin-bottom:2rem;">
        <h1>Wybierz pakiet</h1>
        <p style="color:#6b7280;">Wszystkie pakiety standardowe są ważne 9 miesięcy. Pakiety ślubne — 12 miesięcy, można przedłużać.</p>
    </header>

    @foreach ($packages as $kind => $group)
        <section style="margin-bottom:2.5rem;">
            <h2 style="text-align:center;color:#6b7280;font-size:1rem;text-transform:uppercase;letter-spacing:.05em;margin-bottom:1rem;">
                {{ $kind === 'wedding' ? 'Pakiety ślubne' : 'Listy prezentów' }}
            </h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
                @foreach ($group as $pkg)
                    <div class="card" style="text-align:left;padding:1.25rem;display:flex;flex-direction:column;">
                        <h3 style="margin:0 0 .25rem;">{{ $pkg->name }}</h3>
                        <p style="font-size:1.5rem;font-weight:700;margin:0 0 .75rem;">
                            @if ($pkg->price_pln_gr === 0)
                                <span>0 zł</span>
                            @else
                                {{ number_format($pkg->price_pln_gr / 100, 0, ',', ' ') }} zł
                            @endif
                            <small style="color:#6b7280;font-weight:400;font-size:.85rem;">/ {{ $pkg->valid_days }} dni</small>
                        </p>
                        <ul style="list-style:none;padding:0;color:#4b5563;font-size:.9rem;line-height:1.6;flex:1;">
                            <li>
                                @if ($pkg->gift_limit === null)
                                    Bez limitu prezentów
                                @else
                                    Do {{ $pkg->gift_limit }} prezentów
                                @endif
                            </li>
                            @if ($pkg->hasFeature('custom_slug'))<li>Własny adres dajprezent.pl/{nazwa}</li>@endif
                            @if ($pkg->hasFeature('password_protect'))<li>Ochrona hasłem</li>@endif
                            @if ($pkg->hasFeature('multiple_lists'))<li>Wiele list ({{ $pkg->featureValue('multiple_lists') }})</li>@endif
                            @if ($pkg->hasFeature('export'))<li>Eksport CSV / PDF</li>@endif
                            @if ($pkg->hasFeature('custom_domain'))<li>Własna domena (CNAME)</li>@endif
                            @if ($pkg->hasFeature('gallery'))<li>Galeria po-ślubna</li>@endif
                            @if ($pkg->hasFeature('rsvp_dietary'))<li>RSVP z dietami i alergiami</li>@endif
                            @if ($pkg->hasFeature('invitation_pdf'))<li>Generator zaproszeń PDF + QR</li>@endif
                            @if ($pkg->hasFeature('priority_support'))<li>Priorytetowy support</li>@endif
                            @if ($pkg->hasFeature('remove_branding'))<li>Bez brandingu „DajPrezent.pl"</li>@endif
                        </ul>
                        <a href="{{ route('public.checkout.buy', ['code' => $pkg->code]) }}" class="card-buy"
                           style="display:block;text-align:center;margin-top:1rem;background:#ec4899;color:#fff;padding:.65rem 1rem;border-radius:.5rem;text-decoration:none;font-weight:600;">
                            @if ($pkg->price_pln_gr === 0) Zacznij za darmo @else Wybieram @endif
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
@endsection
