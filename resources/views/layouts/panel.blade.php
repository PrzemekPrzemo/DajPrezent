<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-brand.head :title="View::getSection('title')" robots="noindex,nofollow"/>
</head>
<body class="min-h-screen flex flex-col bg-dp-purple-50/30">
    <nav class="border-b border-dp-purple-50 bg-white sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
            <x-brand.logo size="sm" :href="auth()->check() ? route('owner.dashboard') : route('home')"/>
            <span class="flex items-center gap-3 text-sm">
                <span class="hidden sm:flex items-center gap-0.5 text-xs">
                    @foreach (\App\Http\Middleware\SetLocale::SUPPORTED as $loc)
                        <form method="POST" action="{{ route('locale.switch', ['locale' => $loc]) }}" class="m-0">
                            @csrf
                            <button type="submit"
                                    class="px-1.5 py-0.5 rounded transition
                                           {{ app()->getLocale() === $loc
                                              ? 'font-bold text-dp-purple-700'
                                              : 'font-normal text-dp-muted hover:text-dp-purple-700' }}">
                                {{ strtoupper($loc) }}
                            </button>
                        </form>
                        @if (! $loop->last)<span class="text-gray-300">·</span>@endif
                    @endforeach
                </span>
                @auth
                    <a href="{{ route('owner.account.edit') }}" class="text-dp-muted hover:text-dp-purple-700 hidden sm:inline">{{ auth()->user()->email }}</a>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="dp-btn-ghost px-3 py-1.5 text-xs">{{ __('messages.nav.logout') }}</button>
                    </form>
                @endauth
            </span>
        </div>
    </nav>

    <main class="flex-1 max-w-5xl w-full mx-auto px-4 py-6">
        @if (session('status'))
            <div class="dp-flash-ok mb-4">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="dp-flash-err mb-4">
                @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="text-center text-xs text-dp-muted py-5 border-t border-dp-purple-50 bg-white">
        <a href="{{ route('public.legal.terms') }}" class="hover:text-dp-purple-700">Regulamin</a> ·
        <a href="{{ route('public.legal.privacy') }}" class="hover:text-dp-purple-700">Polityka prywatności</a> ·
        <a href="{{ route('public.faq') }}" class="hover:text-dp-purple-700">FAQ</a> ·
        <a href="{{ route('public.help.index') }}" class="hover:text-dp-purple-700">Pomoc</a> ·
        @auth · <a href="{{ route('owner.support.index') }}" class="hover:text-dp-purple-700">Wsparcie</a> · @endauth
        <a href="{{ route('public.contact') }}" class="hover:text-dp-purple-700">Kontakt</a>
    </footer>

    @include('partials.cookie-banner')
</body>
</html>
