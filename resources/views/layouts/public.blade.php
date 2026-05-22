<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — DajPrezent.pl</title>
    <meta name="description" content="@yield('meta_description', 'Stwórz wymarzoną listę prezentów lub stronę ślubną z RSVP. Bliscy zarezerwują anonimowo.')">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph + Twitter --}}
    <meta property="og:site_name" content="DajPrezent.pl">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('og_title', View::getSection('title') ?? 'DajPrezent.pl')">
    <meta property="og:description" content="@yield('og_description', 'Stwórz wymarzoną listę prezentów i podziel się nią z bliskimi.')">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif

    {{-- Bots (overridable per page) --}}
    @hasSection('robots')
        @yield('robots')
    @else
        <meta name="robots" content="index,follow">
    @endif
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #1f2937; background: #fdf2f8; }
        .container { max-width: 720px; margin: 0 auto; padding: 4rem 1.5rem; }
        .card { background: #fff; border-radius: 1rem; padding: 2.5rem; box-shadow: 0 10px 30px rgba(0,0,0,.06); text-align: center; }
        h1 { margin-top: 0; font-size: 1.75rem; }
        p { color: #4b5563; line-height: 1.6; }
        a.button { display:inline-block; margin-top:1rem; padding:.75rem 1.5rem; background:#ec4899; color:#fff; border-radius:.5rem; text-decoration:none; font-weight:600; }
    </style>
</head>
<body>
    <main class="container">
        @yield('content')
    </main>

    <footer style="text-align:center;color:#9ca3af;font-size:.85rem;padding:1rem 1rem 3rem;max-width:720px;margin:0 auto;">
        <div style="display:flex;gap:1.25rem;justify-content:center;flex-wrap:wrap;margin-bottom:.75rem;">
            <a href="{{ route('public.pricing') }}" style="color:#9ca3af;">Pakiety</a>
            <a href="{{ route('public.faq') }}" style="color:#9ca3af;">FAQ</a>
            <a href="{{ route('public.contact') }}" style="color:#9ca3af;">Kontakt</a>
            <a href="{{ route('public.legal.terms') }}" style="color:#9ca3af;">Regulamin</a>
            <a href="{{ route('public.legal.privacy') }}" style="color:#9ca3af;">Polityka prywatności</a>
        </div>
        © {{ date('Y') }} DajPrezent.pl · Sendormeco Holding, NIP 525-28-66-457
    </footer>
    @include('partials.cookie-banner')
</body>
</html>
