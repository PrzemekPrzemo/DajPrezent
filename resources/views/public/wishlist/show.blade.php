@extends('layouts.public')

@section('title', $tenant->name)
@section('meta_description', 'Lista prezentów: '.$tenant->name.' — zarezerwuj anonimowo na DajPrezent.pl.')
@section('og_title', $tenant->name.' — lista prezentów')
@section('og_description', 'Zarezerwuj prezent dla '.$tenant->name.'. Bez konta, anonimowo.')
@php
    $coverGift = $gifts->whereNotNull('image_path')->first();
@endphp
@if ($coverGift)
    @section('og_image', asset('storage/'.$coverGift->image_path))
@endif
{{-- Personal lists default to noindex — owners share the link directly. --}}
@section('robots')
<meta name="robots" content="noindex,follow">
@endsection

@section('content')
    {{-- After a successful reservation, drop {gift_id ↔ token} into
         localStorage so this browser shows "Twoja rezerwacja" + cancel
         link on future visits. We never tie this to an account — it's
         a per-browser convenience. --}}
    @if (session('just_reserved_gift') && session('just_reserved_token'))
        <script>
            try {
                localStorage.setItem(
                    'dp.reserved.' + {{ (int) session('just_reserved_gift') }},
                    {!! json_encode(session('just_reserved_token'), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
                );
            } catch (e) { /* private mode or quota: silently degrade */ }
        </script>
    @endif

    <header class="bg-dp-gradient text-white rounded-dp-lg p-8 sm:p-10 text-center mb-8 shadow-dp-card">
        <h1 class="font-display text-3xl sm:text-4xl font-bold m-0 text-white">{{ $tenant->name }}</h1>
        <p class="mt-2 text-white/85 m-0">Lista wymarzonych prezentów</p>
    </header>

    @if (session('status'))
        <div role="status" class="bg-emerald-50 text-emerald-800 rounded-dp px-4 py-3 text-sm mb-6">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div role="alert" class="bg-red-50 text-red-800 rounded-dp px-4 py-3 text-sm mb-6">
            @foreach ($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    @if ($gifts->isEmpty())
        <p class="text-center text-dp-muted">Lista jest jeszcze pusta. Wróć tu wkrótce.</p>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" x-data="{open:null}">
        @foreach ($gifts as $gift)
            @php
                $reserved = $gift->status === \App\Domain\Wishlist\Models\Gift::STATUS_RESERVED;
                $received = $gift->status === \App\Domain\Wishlist\Models\Gift::STATUS_RECEIVED;
            @endphp
            <article
                x-data="{ myToken: null }"
                x-init="try { myToken = localStorage.getItem('dp.reserved.{{ $gift->id }}') } catch(e){}"
                class="dp-card text-left p-5 relative transition-all duration-300"
                :class="myToken ? 'ring-2 ring-emerald-400 ring-offset-2 ring-offset-dp-purple-50/30' : ''">

                @if ($gift->image_path)
                    <img src="{{ asset('storage/'.$gift->image_path) }}" alt=""
                         loading="lazy"
                         class="w-full h-40 object-cover rounded-dp mb-3">
                @endif

                <h2 class="font-display text-base font-semibold m-0 mb-1">{{ $gift->title }}</h2>
                @if ($gift->description)
                    <p class="text-dp-muted text-sm m-0 mb-2 leading-relaxed">{{ \Illuminate\Support\Str::limit($gift->description, 120) }}</p>
                @endif
                @if ($gift->price_pln_gr)
                    <p class="font-semibold m-0 mb-3">{{ number_format($gift->price_pln_gr / 100, 2, ',', ' ') }} zł</p>
                @endif

                @if ($received)
                    {{-- Stan: OTRZYMANY (owner oznaczył) --}}
                    <span class="dp-chip dp-chip-received">otrzymany</span>
                @elseif ($reserved)
                    {{-- Stan: ZAREZERWOWANY przez kogoś (lub przez TEGO gościa) --}}
                    <template x-if="myToken">
                        <div>
                            <span class="dp-chip" style="background:#ecfdf5;color:#065f46;">
                                ✓ Twoja rezerwacja
                            </span>
                            <a href="/r/cancel/{{ '' }}" x-bind:href="'/r/cancel/' + encodeURIComponent(myToken)"
                               @click="if (! confirm('Cofnąć Twoją rezerwację tego prezentu?')) $event.preventDefault();
                                       localStorage.removeItem('dp.reserved.{{ $gift->id }}')"
                               class="dp-btn-secondary text-xs px-3 py-1.5 mt-3 inline-flex">
                                Cofnij rezerwację
                            </a>
                        </div>
                    </template>
                    <template x-if="! myToken">
                        <span class="inline-flex items-center gap-1.5 dp-chip"
                              style="background:#fef3c7;color:#92400e;"
                              aria-label="Prezent zarezerwowany">
                            Zarezerwowane <span aria-hidden="true">💗</span>
                        </span>
                    </template>
                @else
                    {{-- Stan: WOLNY --}}
                    <button type="button" x-on:click="open = {{ $gift->id }}"
                            class="dp-btn-primary w-full">
                        Zarezerwuj prezent
                    </button>

                    {{-- Modal rezerwacji --}}
                    <div x-show="open === {{ $gift->id }}" x-cloak
                         class="fixed inset-0 bg-slate-900/45 z-50 flex items-center justify-center p-4"
                         x-on:click.self="open = null"
                         role="dialog" aria-modal="true">
                        <div class="dp-card max-w-md w-full p-6">
                            <h3 class="font-display font-semibold text-lg m-0 mb-2">Rezerwacja: {{ $gift->title }}</h3>
                            <p class="text-dp-muted text-sm mb-4">
                                Podaj swój e-mail — wyślemy link aktywacyjny. Twoja tożsamość pozostanie nieznana właścicielowi listy.
                            </p>
                            <form method="POST" action="{{ route('public.reservations.store', ['slug' => $tenant->slug, 'gift' => $gift->id]) }}">
                                @csrf
                                <div class="dp-field">
                                    <label class="dp-label">E-mail<span aria-hidden="true">*</span>
                                        <input type="email" name="email" required class="dp-input mt-1">
                                    </label>
                                </div>
                                <div class="dp-field">
                                    <label class="dp-label">Imię <span class="font-normal text-dp-muted">(opcjonalnie, zobaczy właściciel po Twojej rezerwacji)</span>
                                        <input type="text" name="name" maxlength="80" class="dp-input mt-1">
                                    </label>
                                </div>
                                <fieldset class="dp-field border-0 p-0 m-0">
                                    <legend class="dp-label">Co chcesz zrobić?</legend>
                                    <label class="flex items-center gap-2 text-sm mt-1">
                                        <input type="radio" name="intent" value="reserve" checked>
                                        Rezerwuję prezent
                                    </label>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="radio" name="intent" value="give">
                                        Daję prezent (kupuję już teraz)
                                    </label>
                                </fieldset>
                                <div class="flex gap-2 justify-end mt-4">
                                    <button type="button" x-on:click="open = null" class="dp-btn-secondary">Anuluj</button>
                                    <button type="submit" class="dp-btn-primary">Wyślij link</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </article>
        @endforeach
    </div>
@endsection
