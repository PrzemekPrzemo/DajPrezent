@extends('layouts.public')

@section('title', 'Pakiety i ceny')
@section('og_image', route('public.og', ['title' => 'Pakiety i ceny', 'subtitle' => 'Od 0 zł do 399 zł']))

@push('head_extra')
    <x-seo.jsonld :data="\App\Domain\Seo\JsonLd::breadcrumbList([
        ['name' => 'Strona główna', 'url' => url('/')],
        ['name' => 'Pakiety', 'url' => route('public.pricing')],
    ])"/>
    <x-seo.jsonld :data="\App\Domain\Seo\JsonLd::offerCatalog($packages->flatten())"/>
@endpush

@section('content')
    <header class="text-center max-w-2xl mx-auto px-4 pt-16 pb-8">
        <h1 class="font-display text-4xl font-bold">Wybierz pakiet</h1>
        <p class="text-dp-muted mt-3">
            Pakiety standardowe ważne 9 miesięcy. Pakiety ślubne — 12 miesięcy z możliwością przedłużenia.
            <br>Faktura VAT przez KSeF wystawiana automatycznie po zakupie.
        </p>
    </header>

    @foreach ($packages as $kind => $group)
        <section class="max-w-6xl mx-auto px-4 pb-12">
            <h2 class="text-xs uppercase tracking-wider text-dp-muted text-center mb-6">
                {{ $kind === 'wedding' ? 'Pakiety ślubne' : 'Listy prezentów' }}
            </h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-{{ min(count($group), 5) }} gap-4">
                @foreach ($group as $pkg)
                    @php $featured = $pkg->code === 'plus' || $pkg->code === 'wedding_premium'; @endphp
                    <div class="relative dp-card flex flex-col {{ $featured ? 'ring-2 ring-dp-purple-500 shadow-dp-card-lg' : '' }}">
                        @if ($featured)
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 dp-chip dp-chip-pink">Najczęściej wybierany</span>
                        @endif
                        <h3 class="font-display font-semibold text-lg">{{ $pkg->name }}</h3>
                        <div class="mt-2 mb-4">
                            <span class="text-3xl font-bold text-dp-navy">
                                @if ($pkg->price_pln_gr === 0) 0 zł
                                @else {{ number_format($pkg->price_pln_gr / 100, 0, ',', ' ') }} zł
                                @endif
                            </span>
                            <span class="text-xs text-dp-muted ml-1">/ {{ $pkg->valid_days }} dni</span>
                        </div>
                        <ul class="space-y-2 text-sm text-dp-navy/80 flex-1">
                            <li class="flex items-start gap-2"><span class="text-dp-green">✓</span>
                                {{ $pkg->gift_limit === null ? 'Bez limitu prezentów' : 'Do '.$pkg->gift_limit.' prezentów' }}
                            </li>
                            @if ($pkg->hasFeature('custom_slug'))<li class="flex items-start gap-2"><span class="text-dp-green">✓</span>Własny adres listy</li>@endif
                            @if ($pkg->hasFeature('password_protect'))<li class="flex items-start gap-2"><span class="text-dp-green">✓</span>Ochrona hasłem</li>@endif
                            @if ($pkg->hasFeature('multiple_lists'))<li class="flex items-start gap-2"><span class="text-dp-green">✓</span>Wiele list ({{ $pkg->featureValue('multiple_lists') }})</li>@endif
                            @if ($pkg->hasFeature('export'))<li class="flex items-start gap-2"><span class="text-dp-green">✓</span>Eksport CSV</li>@endif
                            @if ($pkg->hasFeature('custom_domain'))<li class="flex items-start gap-2"><span class="text-dp-green">✓</span>Własna domena</li>@endif
                            @if ($pkg->hasFeature('gallery'))<li class="flex items-start gap-2"><span class="text-dp-green">✓</span>Galeria po-ślubna</li>@endif
                            @if ($pkg->hasFeature('rsvp_dietary'))<li class="flex items-start gap-2"><span class="text-dp-green">✓</span>RSVP z dietami</li>@endif
                            @if ($pkg->hasFeature('invitation_pdf'))<li class="flex items-start gap-2"><span class="text-dp-green">✓</span>Generator zaproszeń PDF</li>@endif
                            @if ($pkg->hasFeature('priority_support'))<li class="flex items-start gap-2"><span class="text-dp-green">✓</span>Priorytetowy support</li>@endif
                            @if ($pkg->hasFeature('remove_branding'))<li class="flex items-start gap-2"><span class="text-dp-green">✓</span>Bez brandingu</li>@endif
                        </ul>
                        <a href="{{ route('public.checkout.buy', ['code' => $pkg->code]) }}"
                           class="{{ $featured ? 'dp-btn-primary' : 'dp-btn-secondary' }} mt-5 w-full justify-center">
                            @if ($pkg->price_pln_gr === 0) Zacznij za darmo @else Wybieram @endif
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
@endsection
