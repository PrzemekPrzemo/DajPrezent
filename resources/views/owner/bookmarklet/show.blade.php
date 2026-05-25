@extends('layouts.panel')

@section('title', 'Bookmarklet — dodaj prezent z dowolnego sklepu')

@section('content')
    <div class="max-w-3xl mx-auto">
        <header class="text-center mb-8">
            <div class="w-16 h-16 mx-auto mb-3 rounded-dp-lg bg-dp-gradient flex items-center justify-center text-white text-3xl shadow-dp-card">
                🔖
            </div>
            <h1 class="font-display text-2xl sm:text-3xl font-bold m-0">Bookmarklet „Dodaj do DajPrezent.pl"</h1>
            <p class="text-sm text-dp-muted mt-3 max-w-xl mx-auto">
                Mały przycisk na pasku zakładek przeglądarki. Klikasz w sklepie → wpadasz tutaj z prefillowanym formularzem.
                Działa wszędzie (też tam, gdzie autodetekcja serwera jest blokowana — Allegro, Empik).
            </p>
        </header>

        {{-- Drag-target card --}}
        <div class="dp-card text-center mb-6 ring-2 ring-dp-purple-100 bg-dp-purple-50/40">
            <p class="text-xs uppercase tracking-wider text-dp-muted m-0 mb-3">Przeciągnij ten przycisk ↓ na pasek zakładek</p>
            <a class="dp-btn-primary px-7 py-3 text-base inline-flex shadow-dp-card-lg cursor-grab active:cursor-grabbing"
               href='{!! $bookmarkletJs !!}'
               onclick="event.preventDefault(); alert('To nie jest zwykły link — przeciągnij ten przycisk na pasek zakładek przeglądarki.');">
                ❤ Dodaj do DajPrezent.pl
            </a>
            <p class="text-xs text-dp-muted mt-3 m-0">
                Nie klikaj — <strong>przeciągnij</strong> myszką do paska zakładek (zwykle pod paskiem adresu, włączysz go Ctrl+Shift+B).
            </p>
        </div>

        {{-- How it works --}}
        <div class="dp-card mb-6">
            <h2 class="font-display font-semibold text-lg m-0 mb-4">Jak to działa</h2>
            <ol class="space-y-3">
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-7 h-7 rounded-full bg-dp-gradient text-white text-sm font-bold flex items-center justify-center">1</span>
                    <span class="text-sm leading-relaxed">Otwórz dowolny sklep internetowy z prezentem, który chcesz dodać.</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-7 h-7 rounded-full bg-dp-gradient text-white text-sm font-bold flex items-center justify-center">2</span>
                    <span class="text-sm leading-relaxed">Kliknij zakładkę <strong>„❤ Dodaj do DajPrezent.pl"</strong> na pasku przeglądarki.</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-7 h-7 rounded-full bg-dp-gradient text-white text-sm font-bold flex items-center justify-center">3</span>
                    <span class="text-sm leading-relaxed">Otworzy się okienko z prefillowanym formularzem (tytuł, link, cena).</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-7 h-7 rounded-full bg-dp-gradient text-white text-sm font-bold flex items-center justify-center">4</span>
                    <span class="text-sm leading-relaxed">Wybierz listę, popraw co trzeba, kliknij <strong>„Dodaj prezent"</strong>.</span>
                </li>
            </ol>
        </div>

        {{-- Privacy / how-safe --}}
        <div class="dp-card bg-emerald-50/40 border-emerald-100">
            <h2 class="font-display font-semibold text-base m-0 mb-2 flex items-center gap-2">
                <span>🔒</span> Co bookmarklet robi (i czego nie robi)
            </h2>
            <ul class="space-y-1.5 text-sm text-dp-navy/85">
                <li class="flex items-start gap-2"><span class="text-dp-green">✓</span> Odczytuje meta tagi OpenGraph z otwartej strony (tytuł, cena, zdjęcie).</li>
                <li class="flex items-start gap-2"><span class="text-dp-green">✓</span> Przekazuje je do prefillowanego formularza tutaj.</li>
                <li class="flex items-start gap-2"><span class="text-red-500">✗</span> Nie czyta Twoich haseł, cookies, historii, ani danych logowania.</li>
                <li class="flex items-start gap-2"><span class="text-red-500">✗</span> Nie wysyła nic poza dane, które widzisz w formularzu.</li>
            </ul>
            <p class="text-xs text-dp-muted mt-3 m-0">
                Możesz zobaczyć kod skryptu — kliknij prawym przyciskiem na zakładce → „Edytuj…".
            </p>
        </div>

        @if ($tenants->isEmpty())
            <div class="dp-card mt-6 text-center ring-2 ring-amber-200 bg-amber-50/40">
                <p class="text-sm text-amber-900 m-0 mb-3">
                    <strong>Nie masz jeszcze listy.</strong> Bookmarklet zacznie działać dopiero gdy stworzysz pierwszą.
                </p>
                <a href="{{ route('public.pricing') }}" class="dp-btn-primary px-6 py-2.5">
                    Wybierz pakiet →
                </a>
            </div>
        @endif
    </div>
@endsection
