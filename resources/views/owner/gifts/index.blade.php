@extends('layouts.panel')

@section('title', $tenant->name.' — prezenty')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;flex-wrap:wrap;gap:.5rem;">
        <h1 style="margin:0;">{{ $tenant->name }}</h1>
        <div style="display:flex;gap:.5rem;">
            <a href="{{ route('owner.tenant.settings.edit', $tenant) }}" class="btn btn-secondary">Ustawienia</a>
            <a href="{{ route('owner.dashboard') }}" class="btn btn-secondary">← Wszystkie listy</a>
        </div>
    </div>
    <p style="color:#6b7280;margin-top:0;">
        Publiczny adres: <a href="/{{ $tenant->slug }}" target="_blank">dajprezent.pl/{{ $tenant->slug }}</a>
    </p>

    <div class="card">
        <h2>Dodaj prezent</h2>
        <form method="POST" action="{{ route('owner.gifts.store', $tenant) }}" enctype="multipart/form-data">
            @csrf
            <div class="field">
                <label for="title">Tytuł*</label>
                <input id="title" type="text" name="title" maxlength="120" required>
            </div>
            <div class="field">
                <label for="url">Link do sklepu</label>
                <input id="url" type="url" name="url" maxlength="1024" placeholder="https://...">
            </div>
            <div class="field" style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <div>
                    <label for="price_pln">Cena (zł)</label>
                    <input id="price_pln" type="number" step="0.01" min="0" name="price_pln">
                </div>
                <div>
                    <label for="priority">Priorytet</label>
                    <select id="priority" name="priority">
                        <option value="1">1 — muszę mieć</option>
                        <option value="2" selected>2 — normalny</option>
                        <option value="3">3 — nice to have</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="image">Zdjęcie (JPG/PNG/WebP, do 4 MB)</label>
                <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="field">
                <label for="description">Opis (opcjonalnie)</label>
                <textarea id="description" name="description" rows="2" maxlength="2000"></textarea>
            </div>
            <div class="field" style="text-align:right;">
                <button type="submit">Dodaj prezent</button>
            </div>
        </form>
    </div>

    @php
        $activeSub = $tenant->subscriptions->where('status', 'active')->sortByDesc('paid_at')->first()
            ?? $tenant->subscriptions()->where('status', 'active')->with('package')->orderByDesc('paid_at')->first();
        $canExport = $activeSub?->package?->hasFeature('export') ?? false;
    @endphp

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap;">
            <h2 style="margin:0;">Lista prezentów ({{ $gifts->count() }})</h2>
            @if ($canExport)
                <a href="{{ route('owner.gifts.export.csv', $tenant) }}" class="btn btn-secondary">⬇ Eksport CSV</a>
            @endif
        </div>

        @if ($gifts->isEmpty())
            <p style="color:#6b7280;">Brak prezentów. Dodaj pierwszy formularzem powyżej.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Tytuł</th>
                        <th>Cena</th>
                        <th>Priorytet</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($gifts as $gift)
                    <tr>
                        <td style="display:flex;align-items:center;gap:.75rem;">
                            @if ($gift->image_path)
                                <img src="{{ asset('storage/'.$gift->image_path) }}" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:.375rem;flex-shrink:0;">
                            @endif
                            <div>
                                <strong>{{ $gift->title }}</strong>
                                @if ($gift->url)
                                    <div><a href="{{ $gift->url }}" target="_blank" rel="noopener" style="font-size:.85rem;">{{ \Illuminate\Support\Str::limit(parse_url($gift->url, PHP_URL_HOST) ?: $gift->url, 40) }}</a></div>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if ($gift->price_pln_gr)
                                {{ number_format($gift->price_pln_gr / 100, 2, ',', ' ') }} zł
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td>
                            @switch($gift->priority)
                                @case(1) <span style="color:#b91c1c;">★★★</span> @break
                                @case(2) <span>★★</span> @break
                                @case(3) <span style="color:#9ca3af;">★</span> @break
                            @endswitch
                        </td>
                        <td>
                            @switch($gift->status)
                                @case(\App\Domain\Wishlist\Models\Gift::STATUS_AVAILABLE)
                                    <span class="chip chip-available">dostępny</span>
                                    @break
                                @case(\App\Domain\Wishlist\Models\Gift::STATUS_RESERVED)
                                    <span class="chip chip-reserved">zarezerwowany</span>
                                    @break
                                @case(\App\Domain\Wishlist\Models\Gift::STATUS_RECEIVED)
                                    <span class="chip chip-received">otrzymany</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="row-actions" style="text-align:right;">
                            @if ($gift->status === \App\Domain\Wishlist\Models\Gift::STATUS_RESERVED)
                                <form method="POST" action="{{ route('owner.gifts.received', [$tenant, $gift]) }}">
                                    @csrf
                                    <button type="submit" class="btn">Oznacz: otrzymany</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('owner.gifts.destroy', [$tenant, $gift]) }}" onsubmit="return confirm('Usunąć prezent &quot;{{ $gift->title }}&quot;?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Usuń</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
