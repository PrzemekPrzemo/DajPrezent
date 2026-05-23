@extends('layouts.panel')

@section('title', $tenant->name.' — prezenty')

@php
    $activeSub = $tenant->subscriptions->where('status', 'active')->sortByDesc('paid_at')->first()
        ?? $tenant->subscriptions()->where('status', 'active')->with('package')->orderByDesc('paid_at')->first();
    $canExport = $activeSub?->package?->hasFeature('export') ?? false;
@endphp

@section('content')
    <header class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display text-2xl sm:text-3xl font-bold m-0">{{ $tenant->name }}</h1>
            <p class="text-sm text-dp-muted mt-1 m-0">
                Publiczny adres: <a href="/{{ $tenant->slug }}" target="_blank"
                                    class="text-dp-purple-700 hover:underline">dajprezent.pl/{{ $tenant->slug }}</a>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" @click="$dispatch('open-gift-drawer')"
                    class="dp-btn-primary">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Dodaj prezent
            </button>
            <a href="{{ route('owner.tenant.settings.edit', $tenant) }}" class="dp-btn-secondary">Ustawienia</a>
            <a href="{{ route('owner.dashboard') }}" class="dp-btn-ghost">← Listy</a>
        </div>
    </header>

    <div class="dp-card">
        <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
            <h2 class="font-display font-semibold text-lg m-0">Prezenty ({{ $gifts->count() }})</h2>
            @if ($canExport)
                <a href="{{ route('owner.gifts.export.csv', $tenant) }}" class="dp-btn-secondary">⬇ Eksport CSV</a>
            @endif
        </div>

        @if ($gifts->isEmpty())
            {{-- EMPTY STATE z dokumentu UX/UI --}}
            <div class="text-center py-12 px-4">
                <div class="w-20 h-20 mx-auto mb-4 rounded-dp-lg bg-dp-gradient flex items-center justify-center text-white text-3xl shadow-dp-card">
                    🎁
                </div>
                <h3 class="font-display font-semibold text-lg mb-1">Stwórz swoją pierwszą listę marzeń</h3>
                <p class="text-sm text-dp-muted max-w-md mx-auto mb-5">
                    Dodaj prezenty wklejając linki ze sklepów — tytuł, cena i zdjęcie pobiorą się automatycznie.
                </p>
                <button type="button" @click="$dispatch('open-gift-drawer')"
                        class="dp-btn-primary px-6 py-3">
                    + Dodaj pierwszy prezent
                </button>
            </div>
        @else
            <div class="overflow-x-auto -mx-6 px-6">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase tracking-wide text-dp-muted border-b border-slate-100">
                        <tr>
                            <th class="text-left py-2 px-2">Tytuł</th>
                            <th class="text-right py-2 px-2">Cena</th>
                            <th class="text-center py-2 px-2">Priorytet</th>
                            <th class="text-center py-2 px-2">Status</th>
                            <th class="py-2 px-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($gifts as $gift)
                        <tr class="border-b border-slate-50 last:border-0">
                            <td class="py-3 px-2">
                                <div class="flex items-center gap-3">
                                    @if ($gift->image_path)
                                        <img src="{{ asset('storage/'.$gift->image_path) }}" alt=""
                                             class="w-14 h-14 object-cover rounded-dp flex-shrink-0">
                                    @else
                                        <div class="w-14 h-14 rounded-dp bg-dp-purple-50/50 flex items-center justify-center text-dp-purple-300 text-xl flex-shrink-0" aria-hidden="true">🎁</div>
                                    @endif
                                    <div class="min-w-0">
                                        <strong class="block truncate">{{ $gift->title }}</strong>
                                        @if ($gift->url)
                                            <a href="{{ $gift->url }}" target="_blank" rel="noopener noreferrer nofollow"
                                               class="text-xs text-dp-muted hover:text-dp-purple-700">
                                                {{ \Illuminate\Support\Str::limit(parse_url($gift->url, PHP_URL_HOST) ?: $gift->url, 40) }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-2 text-right whitespace-nowrap">
                                @if ($gift->price_pln_gr)
                                    {{ number_format($gift->price_pln_gr / 100, 2, ',', ' ') }} zł
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-2 text-center">
                                @switch($gift->priority)
                                    @case(1) <span class="text-red-600" title="muszę mieć">★★★</span> @break
                                    @case(2) <span class="text-slate-500" title="normalny">★★</span> @break
                                    @case(3) <span class="text-slate-300" title="nice to have">★</span> @break
                                @endswitch
                            </td>
                            <td class="py-3 px-2 text-center">
                                @switch($gift->status)
                                    @case(\App\Domain\Wishlist\Models\Gift::STATUS_AVAILABLE)
                                        <span class="dp-chip dp-chip-available">dostępny</span>
                                        @break
                                    @case(\App\Domain\Wishlist\Models\Gift::STATUS_RESERVED)
                                        <span class="dp-chip dp-chip-reserved">zarezerwowany</span>
                                        @break
                                    @case(\App\Domain\Wishlist\Models\Gift::STATUS_RECEIVED)
                                        <span class="dp-chip dp-chip-received inline-flex items-center gap-1">
                                            @if (session('dp_heart') === $gift->id)
                                                <span class="text-dp-pink inline-block dp-heart-pulse" aria-hidden="true">♥</span>
                                            @endif
                                            otrzymany
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td class="py-3 px-2 text-right whitespace-nowrap">
                                @if ($gift->status === \App\Domain\Wishlist\Models\Gift::STATUS_RESERVED)
                                    <form method="POST" action="{{ route('owner.gifts.received', [$tenant, $gift]) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="dp-btn-secondary text-xs px-3 py-1.5">Otrzymany</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('owner.gifts.destroy', [$tenant, $gift]) }}"
                                      onsubmit="return confirm('Usunąć prezent &quot;{{ $gift->title }}&quot;?');"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dp-btn-ghost text-xs px-3 py-1.5 text-red-600 hover:bg-red-50">Usuń</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <x-gift.drawer :tenant="$tenant"/>
@endsection
