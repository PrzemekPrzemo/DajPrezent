@extends('layouts.public')

@section('title', $title)
@section('meta_description', $meta_description)
@section('og_image', route('public.og', ['title' => $h1]))

@push('head_extra')
    <x-seo.jsonld :data="\App\Domain\Seo\JsonLd::breadcrumbList([
        ['name' => 'Strona główna', 'url' => url('/')],
        ['name' => $title, 'url' => url()->current()],
    ])"/>
    <x-seo.jsonld :data="\App\Domain\Seo\JsonLd::organization()"/>
@endpush

@section('content')
    <section class="dp-hero-bg relative overflow-hidden">
        <div class="absolute -top-32 -right-32 w-[420px] h-[420px] bg-dp-purple-200/40 rounded-full blur-3xl dp-bloom"></div>
        <div class="relative max-w-4xl mx-auto px-4 py-20 sm:py-24 text-center">
            <span class="dp-chip dp-chip-pink mb-4">{{ $kicker }}</span>
            <span class="sr-only">Tag: {{ $tag }}</span>
            <h1 class="font-display text-4xl sm:text-5xl font-bold leading-tight">
                {{ $h1 }}
            </h1>
            <p class="mt-5 text-lg text-dp-muted max-w-2xl mx-auto">{{ $lead }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('public.checkout.buy', ['code' => $cta_package]) }}" class="dp-btn-primary px-7 py-3 text-base">
                    Załóż listę →
                </a>
                <a href="{{ route('public.pricing') }}" class="dp-btn-ghost px-4 py-3">Wszystkie pakiety</a>
            </div>
        </div>
    </section>

    <section class="max-w-5xl mx-auto px-4 py-16">
        <div class="grid sm:grid-cols-3 gap-5">
            @foreach ($use_cases as $u)
                <div class="dp-card">
                    <div class="text-3xl mb-3" aria-hidden="true">{{ $u['icon'] }}</div>
                    <h2 class="font-display font-semibold text-lg">{{ $u['title'] }}</h2>
                    <p class="text-sm text-dp-muted mt-2 leading-relaxed">{{ $u['body'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="bg-dp-gradient text-white py-14">
        <div class="max-w-3xl mx-auto px-4 text-center">
            <h2 class="font-display text-2xl sm:text-3xl font-bold">
                Twoja lista — w 3 minuty.
            </h2>
            <p class="mt-3 text-white/85">
                Bez konta dla gości, bez reklam, z fakturą VAT w cenie. Działa na każdym telefonie.
            </p>
            <a href="{{ route('public.pricing') }}" class="dp-btn mt-6 px-7 py-3 bg-white text-dp-purple-700 hover:shadow-dp-card-lg inline-flex">
                Wybieram pakiet →
            </a>
        </div>
    </section>
@endsection
