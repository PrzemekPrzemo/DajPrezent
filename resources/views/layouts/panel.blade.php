<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — DajPrezent.pl</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #1f2937; background: #f9fafb; }
        a { color: #db2777; }
        .nav { background: #fff; border-bottom: 1px solid #e5e7eb; padding: .75rem 1.25rem; display: flex; align-items: center; justify-content: space-between; }
        .nav .brand { font-weight: 700; color: #1f2937; text-decoration: none; }
        .nav .user { display: flex; align-items: center; gap: 1rem; font-size: .9rem; color: #6b7280; }
        .container { max-width: 960px; margin: 0 auto; padding: 2rem 1.25rem; }
        .card { background: #fff; border-radius: .75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.05); border: 1px solid #f3f4f6; }
        .card + .card { margin-top: 1rem; }
        h1 { font-size: 1.5rem; margin: 0 0 1rem; }
        h2 { font-size: 1.15rem; margin: 0 0 .75rem; }
        label { display: block; font-size: .9rem; font-weight: 600; margin-bottom: .25rem; }
        input[type=text], input[type=email], input[type=password], input[type=number], input[type=url], textarea, select {
            display: block; width: 100%; padding: .5rem .75rem; border: 1px solid #d1d5db; border-radius: .375rem; font-size: 1rem; font-family: inherit;
        }
        .field + .field { margin-top: .75rem; }
        button, .btn { background: #ec4899; color: #fff; border: 0; padding: .55rem 1rem; border-radius: .5rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; font-size: .95rem; font-family: inherit; }
        .btn-secondary { background: #e5e7eb; color: #111827; }
        .btn-danger { background: #b91c1c; }
        .flash { padding: .75rem 1rem; border-radius: .5rem; margin-bottom: 1rem; }
        .flash-ok { background: #dcfce7; color: #166534; }
        .flash-err { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .5rem .75rem; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        th { font-size: .8rem; text-transform: uppercase; letter-spacing: .03em; color: #6b7280; }
        .chip { display: inline-block; padding: .15rem .55rem; border-radius: 9999px; font-size: .75rem; font-weight: 600; }
        .chip-available { background: #e0f2fe; color: #075985; }
        .chip-reserved { background: #fef3c7; color: #92400e; }
        .chip-received { background: #dcfce7; color: #166534; }
        .row-actions form { display: inline; }
    </style>
</head>
<body>
    <nav class="nav">
        <a class="brand" href="{{ auth()->check() ? route('owner.dashboard') : route('home') }}">DajPrezent.pl</a>
        <span class="user">
            <span style="display:inline-flex;gap:.25rem;align-items:center;font-size:.85rem;">
                @foreach (\App\Http\Middleware\SetLocale::SUPPORTED as $loc)
                    <form method="POST" action="{{ route('locale.switch', ['locale' => $loc]) }}" style="margin:0;">
                        @csrf
                        <button type="submit" style="background:transparent;border:0;cursor:pointer;font-weight:{{ app()->getLocale() === $loc ? '700' : '400' }};color:{{ app()->getLocale() === $loc ? '#db2777' : '#9ca3af' }};font-size:.85rem;padding:0 .25rem;">
                            {{ strtoupper($loc) }}
                        </button>
                    </form>
                    @if (! $loop->last)<span style="color:#e5e7eb;">|</span>@endif
                @endforeach
            </span>
            @auth
                · <a href="{{ route('owner.account.edit') }}" style="color:#6b7280;text-decoration:none;">{{ auth()->user()->email }}</a>
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="padding:.35rem .8rem;font-size:.85rem;">{{ __('messages.nav.logout') }}</button>
                </form>
            @endauth
        </span>
    </nav>
    <main class="container">
        @if (session('status'))
            <div class="flash flash-ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="flash flash-err">
                @foreach ($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        @endif
        @yield('content')
    </main>

    <footer style="text-align:center;color:#9ca3af;font-size:.8rem;padding:1.5rem 1rem 2rem;">
        <a href="{{ route('public.legal.terms') }}" style="color:#9ca3af;">Regulamin</a> ·
        <a href="{{ route('public.legal.privacy') }}" style="color:#9ca3af;">Polityka prywatności</a> ·
        <a href="{{ route('public.faq') }}" style="color:#9ca3af;">FAQ</a> ·
        <a href="{{ route('public.contact') }}" style="color:#9ca3af;">Kontakt</a>
    </footer>
    @include('partials.cookie-banner')
</body>
</html>
