@extends('layouts.panel')

@section('title', 'Import prezentów z CSV — '.$tenant->name)

@section('content')
    <header class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <a href="{{ route('owner.gifts.index', $tenant) }}" class="text-sm text-dp-muted hover:text-dp-purple-700">← {{ $tenant->name }}</a>
            <h1 class="font-display text-2xl sm:text-3xl font-bold m-0 mt-1">Import prezentów z pliku CSV</h1>
        </div>
    </header>

    <div class="grid lg:grid-cols-[1fr,360px] gap-6">
        <form method="POST" action="{{ route('owner.gifts.import.store', $tenant) }}"
              enctype="multipart/form-data" class="dp-card space-y-4">
            @csrf
            <div class="dp-field">
                <label class="dp-label" for="csv">Plik CSV (max 512 KB)</label>
                <input id="csv" name="csv" type="file" accept=".csv,text/csv"
                       required class="dp-input file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:bg-dp-purple-50 file:text-dp-purple-700 file:font-semibold">
                <p class="text-xs text-dp-muted mt-1">
                    Akceptujemy nagłówki PL (<code>tytuł, opis, cena, link, priorytet</code>)
                    lub EN (<code>title, description, price, url, priority</code>).
                    Separator: przecinek lub średnik (Excel PL).
                </p>
            </div>

            <button type="submit" class="dp-btn-primary px-6">⬆ Zaimportuj prezenty</button>
        </form>

        <aside class="dp-card text-sm">
            <h2 class="font-display font-semibold text-base mb-2">Przykładowy plik</h2>
            <pre class="bg-slate-50 rounded-dp p-3 overflow-x-auto text-xs leading-relaxed"><code>tytuł;cena;link;priorytet
Aparat Instax mini;299,00;https://...;1
Książka „Atomic Habits";49,90;https://...;2
Monstera Deliciosa;89,00;;3</code></pre>
            <ul class="mt-3 space-y-1.5 text-xs text-dp-muted">
                <li>• Tylko kolumna <strong>tytuł / title</strong> jest wymagana.</li>
                <li>• Cena akceptuje „299", „299,99", „299 zł", „299.99".</li>
                <li>• Priorytet 1 = muszę mieć, 2 = normalny, 3 = fajnie byłoby.</li>
                <li>• Max 500 wierszy na jeden import.</li>
                <li>• Limit pakietu jest egzekwowany — nadmiar zostaje pominięty.</li>
            </ul>
        </aside>
    </div>
@endsection
