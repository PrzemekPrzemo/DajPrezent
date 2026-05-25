@extends('layouts.public')

@section('title', __('messages.contact.h1'))

@section('content')
    <section class="max-w-3xl mx-auto px-4 pt-16 pb-12">
        <header class="text-center mb-8">
            <h1 class="font-display text-4xl sm:text-5xl font-bold m-0">{{ __('messages.contact.h1') }}</h1>
            <p class="text-dp-muted mt-3">{{ __('messages.contact.lead') }}</p>
        </header>

        <div class="grid sm:grid-cols-3 gap-4 mb-8">
            <a href="mailto:kontakt@dajprezent.pl" class="dp-card text-center hover:-translate-y-1 transition-transform">
                <div class="text-3xl mb-2">📧</div>
                <h2 class="font-display font-semibold text-sm m-0">{{ __('messages.contact.mail_support') }}</h2>
                <p class="text-xs text-dp-purple-700 mt-1 m-0 break-all">kontakt@dajprezent.pl</p>
            </a>
            <a href="mailto:faktury@dajprezent.pl" class="dp-card text-center hover:-translate-y-1 transition-transform">
                <div class="text-3xl mb-2">🧾</div>
                <h2 class="font-display font-semibold text-sm m-0">{{ __('messages.contact.mail_invoices') }}</h2>
                <p class="text-xs text-dp-purple-700 mt-1 m-0 break-all">faktury@dajprezent.pl</p>
            </a>
            <a href="mailto:rodo@dajprezent.pl" class="dp-card text-center hover:-translate-y-1 transition-transform">
                <div class="text-3xl mb-2">🔒</div>
                <h2 class="font-display font-semibold text-sm m-0">{{ __('messages.contact.mail_rodo') }}</h2>
                <p class="text-xs text-dp-purple-700 mt-1 m-0 break-all">rodo@dajprezent.pl</p>
            </a>
        </div>

        <div class="dp-card bg-dp-purple-50/40">
            <h2 class="font-display font-semibold text-lg m-0 mb-2">{{ __('messages.contact.company_name') }}</h2>
            <p class="text-sm text-dp-navy leading-relaxed m-0">
                <strong>Sendormeco Holding sp. z o.o.</strong><br>
                ul. Złota 75A/7, 00-819 Warszawa<br>
                KRS 0000906110 · NIP 5252866457 · REGON 389194801<br>
                <span class="text-dp-muted text-xs">
                    Kapitał zakładowy 5 000 zł ·
                    Sąd Rejonowy dla m.st. Warszawy w Warszawie, XII Wydział Gospodarczy KRS
                </span>
            </p>
        </div>
    </section>
@endsection
