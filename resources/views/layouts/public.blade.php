<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $robotsRaw = View::hasSection('robots') ? View::getSection('robots') : null;
        $robots = 'index,follow';
        if (is_string($robotsRaw) && preg_match('/content="([^"]+)"/', $robotsRaw, $m)) {
            $robots = $m[1];
        }
    @endphp
    <x-brand.head
        :title="View::hasSection('title') ? View::getSection('title') : null"
        :description="View::hasSection('meta_description') ? View::getSection('meta_description') : null"
        :og-title="View::hasSection('og_title') ? View::getSection('og_title') : null"
        :og-description="View::hasSection('og_description') ? View::getSection('og_description') : null"
        :og-image="View::hasSection('og_image') ? View::getSection('og_image') : null"
        :robots="$robots"
    />
</head>
<body class="min-h-screen flex flex-col">
    <nav class="border-b border-dp-purple-50 bg-white/80 backdrop-blur sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
            <x-brand.logo size="sm"/>
            <div class="flex items-center gap-2 text-sm">
                <a href="{{ route('public.pricing') }}" class="dp-btn-ghost px-3 py-1.5">Pakiety</a>
                <a href="{{ route('public.faq') }}" class="dp-btn-ghost px-3 py-1.5 hidden sm:inline-flex">FAQ</a>
                @auth
                    <a href="{{ route('owner.dashboard') }}" class="dp-btn-secondary px-3 py-1.5">Panel</a>
                @else
                    <a href="{{ route('login') }}" class="dp-btn-ghost px-3 py-1.5 hidden sm:inline-flex">Zaloguj</a>
                    <a href="{{ route('public.pricing') }}" class="dp-btn-primary px-3 py-1.5">Załóż listę</a>
                @endauth
            </div>
        </div>
    </nav>

    <main class="flex-1">
        @if (session('status'))
            <div class="max-w-3xl mx-auto px-4 mt-4">
                <div class="dp-flash-ok">{{ session('status') }}</div>
            </div>
        @endif
        @if ($errors->any())
            <div class="max-w-3xl mx-auto px-4 mt-4">
                <div class="dp-flash-err">
                    @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                </div>
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="border-t border-dp-purple-50 bg-dp-purple-50/30 mt-16">
        <div class="max-w-6xl mx-auto px-4 py-10 grid gap-8 sm:grid-cols-4 text-sm">
            <div class="sm:col-span-2">
                <x-brand.logo :tagline="true"/>
                <p class="text-dp-muted mt-3 text-xs leading-relaxed max-w-xs">
                    Stwórz wymarzoną listę prezentów lub stronę ślubną z RSVP.
                    Bliscy zarezerwują anonimowo.
                </p>
            </div>
            <div>
                <div class="font-semibold text-dp-navy mb-2">Produkt</div>
                <ul class="space-y-1.5 text-dp-muted">
                    <li><a href="{{ route('public.pricing') }}" class="hover:text-dp-purple-700">Pakiety</a></li>
                    <li><a href="{{ route('public.faq') }}" class="hover:text-dp-purple-700">FAQ</a></li>
                    <li><a href="{{ route('public.contact') }}" class="hover:text-dp-purple-700">Kontakt</a></li>
                </ul>
            </div>
            <div>
                <div class="font-semibold text-dp-navy mb-2">Prawo</div>
                <ul class="space-y-1.5 text-dp-muted">
                    <li><a href="{{ route('public.legal.terms') }}" class="hover:text-dp-purple-700">Regulamin</a></li>
                    <li><a href="{{ route('public.legal.privacy') }}" class="hover:text-dp-purple-700">Polityka prywatności</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-dp-purple-50">
            <div class="max-w-6xl mx-auto px-4 py-4 text-xs text-dp-muted flex flex-col sm:flex-row sm:justify-between gap-2">
                <span>© {{ date('Y') }} DajPrezent.pl · Sendormeco Holding sp. z o.o.</span>
                <span>ul. Złota 75A/7, 00-819 Warszawa · KRS 0000906110 · NIP 5252866457 · REGON 389194801</span>
            </div>
        </div>
    </footer>

    @include('partials.cookie-banner')
</body>
</html>
