@extends('layouts.public')

@section('title', $tenant->name)

@section('content')
    <header style="text-align:center; margin-bottom: 2.5rem;">
        <h1 style="margin-bottom:.5rem;">{{ $tenant->name }}</h1>
        <p style="color:#6b7280;">Lista wymarzonych prezentów</p>
    </header>

    @if (session('status'))
        <div role="status" style="background:#dcfce7;color:#166534;padding:.75rem 1rem;border-radius:.5rem;margin-bottom:1.5rem;">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div role="alert" style="background:#fee2e2;color:#991b1b;padding:.75rem 1rem;border-radius:.5rem;margin-bottom:1.5rem;">
            @foreach ($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    @if ($gifts->isEmpty())
        <p style="text-align:center;color:#6b7280;">Lista jest jeszcze pusta. Wróć tu wkrótce.</p>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem;" x-data="{open:null}">
        @foreach ($gifts as $gift)
            @php
                $reserved = $gift->status === \App\Domain\Wishlist\Models\Gift::STATUS_RESERVED;
                $received = $gift->status === \App\Domain\Wishlist\Models\Gift::STATUS_RECEIVED;
            @endphp
            <article class="card" style="text-align:left;padding:1.25rem;position:relative;">
                @if ($gift->image_path)
                    <img src="{{ asset('storage/'.$gift->image_path) }}" alt="" style="width:100%;height:160px;object-fit:cover;border-radius:.5rem;margin-bottom:.75rem;">
                @endif
                <h2 style="font-size:1.05rem;margin:0 0 .5rem;">{{ $gift->title }}</h2>
                @if ($gift->description)
                    <p style="color:#6b7280;font-size:.9rem;margin:0 0 .75rem;">{{ \Illuminate\Support\Str::limit($gift->description, 120) }}</p>
                @endif
                @if ($gift->price_pln_gr)
                    <p style="font-weight:600;margin:0 0 .75rem;">{{ number_format($gift->price_pln_gr / 100, 2, ',', ' ') }} zł</p>
                @endif

                @if ($received)
                    <span style="display:inline-block;background:#e5e7eb;color:#374151;padding:.25rem .6rem;border-radius:9999px;font-size:.8rem;">otrzymany</span>
                @elseif ($reserved)
                    <span style="display:inline-block;background:#fef3c7;color:#92400e;padding:.25rem .6rem;border-radius:9999px;font-size:.8rem;">zarezerwowany</span>
                @else
                    <button type="button"
                            x-on:click="open = {{ $gift->id }}"
                            style="background:#ec4899;color:#fff;border:0;padding:.5rem 1rem;border-radius:.5rem;cursor:pointer;font-weight:600;">
                        Zarezerwuj / Daj prezent
                    </button>

                    <div x-show="open === {{ $gift->id }}" x-cloak
                         style="position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;padding:1rem;z-index:50;"
                         x-on:click.self="open = null">
                        <div class="card" style="max-width:420px;width:100%;text-align:left;padding:1.5rem;">
                            <h3 style="margin-top:0;">Rezerwacja: {{ $gift->title }}</h3>
                            <p style="color:#6b7280;font-size:.9rem;">Podaj swój e-mail — wyślemy link aktywacyjny. Twoja tożsamość pozostanie nieznana właścicielowi listy.</p>
                            <form method="POST" action="{{ route('public.reservations.store', ['slug' => $tenant->slug, 'gift' => $gift->id]) }}">
                                @csrf
                                <label style="display:block;margin-bottom:.5rem;font-size:.9rem;font-weight:600;">E-mail*
                                    <input type="email" name="email" required style="display:block;width:100%;margin-top:.25rem;padding:.5rem;border:1px solid #d1d5db;border-radius:.375rem;">
                                </label>
                                <label style="display:block;margin-bottom:.75rem;font-size:.9rem;font-weight:600;">Imię (opcjonalnie)
                                    <input type="text" name="name" maxlength="80" style="display:block;width:100%;margin-top:.25rem;padding:.5rem;border:1px solid #d1d5db;border-radius:.375rem;">
                                </label>
                                <fieldset style="border:0;padding:0;margin:0 0 .75rem;">
                                    <legend style="font-size:.9rem;font-weight:600;margin-bottom:.25rem;">Co chcesz zrobić?</legend>
                                    <label style="display:block;font-size:.9rem;"><input type="radio" name="intent" value="reserve" checked> Rezerwuję prezent</label>
                                    <label style="display:block;font-size:.9rem;"><input type="radio" name="intent" value="give"> Daję prezent</label>
                                </fieldset>
                                <div style="display:flex;gap:.5rem;justify-content:flex-end;">
                                    <button type="button" x-on:click="open = null" style="background:#e5e7eb;color:#111827;border:0;padding:.5rem 1rem;border-radius:.5rem;cursor:pointer;">Anuluj</button>
                                    <button type="submit" style="background:#ec4899;color:#fff;border:0;padding:.5rem 1rem;border-radius:.5rem;cursor:pointer;font-weight:600;">Wyślij link</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </article>
        @endforeach
    </div>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection
