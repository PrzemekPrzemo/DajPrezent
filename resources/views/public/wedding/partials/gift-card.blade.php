@php
    $reserved = $gift->status === \App\Domain\Wishlist\Models\Gift::STATUS_RESERVED;
    $received = $gift->status === \App\Domain\Wishlist\Models\Gift::STATUS_RECEIVED;
@endphp
<article
    x-data="{ myToken: null }"
    x-init="try { myToken = localStorage.getItem('dp.reserved.{{ $gift->id }}') } catch(e){}"
    class="dp-card text-left p-5 relative"
    :class="myToken ? 'ring-2 ring-emerald-400 ring-offset-2' : ''">

    @if ($gift->image_path)
        <img src="{{ asset('storage/'.$gift->image_path) }}" alt="" loading="lazy"
             class="w-full h-40 object-cover rounded-dp mb-3">
    @endif

    <h3 class="font-display text-base font-semibold m-0 mb-1">{{ $gift->title }}</h3>
    @if ($gift->price_pln_gr)
        <p class="font-semibold m-0 mb-3">{{ number_format($gift->price_pln_gr / 100, 2, ',', ' ') }} zł</p>
    @endif

    @if ($received)
        <span class="dp-chip dp-chip-received">otrzymany</span>
    @elseif ($reserved)
        <template x-if="myToken">
            <div>
                <span class="dp-chip" style="background:#ecfdf5;color:#065f46;">✓ Twoja rezerwacja</span>
                <a x-bind:href="'/r/cancel/' + encodeURIComponent(myToken)"
                   @click="if (! confirm('Cofnąć rezerwację?')) $event.preventDefault();
                           localStorage.removeItem('dp.reserved.{{ $gift->id }}')"
                   class="dp-btn-secondary text-xs px-3 py-1.5 mt-3 inline-flex">Cofnij rezerwację</a>
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
        <button type="button" x-on:click="open = {{ $gift->id }}" class="dp-btn-primary w-full">
            Zarezerwuj
        </button>
        <div x-show="open === {{ $gift->id }}" x-cloak
             class="fixed inset-0 bg-slate-900/45 z-50 flex items-center justify-center p-4"
             x-on:click.self="open = null" role="dialog" aria-modal="true">
            <div class="dp-card max-w-md w-full p-6">
                <h4 class="font-display font-semibold text-lg m-0 mb-2">Rezerwacja: {{ $gift->title }}</h4>
                <p class="text-dp-muted text-sm mb-4">
                    Podaj swój e-mail — wyślemy link aktywacyjny. Twoja tożsamość pozostanie nieznana parze młodej.
                </p>
                <form method="POST" action="{{ route('public.reservations.store', ['slug' => $tenant->slug, 'gift' => $gift->id]) }}">
                    @csrf
                    <div class="dp-field">
                        <label class="dp-label">E-mail*
                            <input type="email" name="email" required class="dp-input mt-1">
                        </label>
                    </div>
                    <div class="dp-field">
                        <label class="dp-label">Imię (opcjonalnie)
                            <input type="text" name="name" maxlength="80" class="dp-input mt-1">
                        </label>
                    </div>
                    <input type="hidden" name="intent" value="reserve">
                    <div class="flex gap-2 justify-end mt-4">
                        <button type="button" x-on:click="open = null" class="dp-btn-secondary">Anuluj</button>
                        <button type="submit" class="dp-btn-primary">Wyślij link</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</article>
