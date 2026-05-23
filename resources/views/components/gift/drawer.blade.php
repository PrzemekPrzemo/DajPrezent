@props([
    'tenant',
    'previewUrl' => null,
])

{{--
    Add-Gift drawer.

    Slides in from the right on desktop, from the bottom on mobile.
    URL field hooks paste/blur to /panel/api/gift-preview which
    server-side-scrapes allowlisted shops and autopopulates the form.

    Activation: any element with @click="$dispatch('open-gift-drawer')".
    Close: ESC / overlay click / Cancel button.
--}}
<div
    x-data="dpGiftDrawer({
        previewUrl: @js($previewUrl ?? route('owner.api.gift-preview')),
        csrf: document.querySelector('meta[name=csrf-token]')?.content ?? ''
    })"
    x-on:open-gift-drawer.window="open()"
    x-on:keydown.escape.window="show && close()"
    role="dialog" aria-modal="true" aria-labelledby="dp-drawer-title"
>
    {{-- Overlay --}}
    <div x-show="show" x-cloak x-transition.opacity
         class="fixed inset-0 bg-slate-900/45 z-40"
         @click="close()" aria-hidden="true"></div>

    {{-- Panel --}}
    <aside x-show="show" x-cloak
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="translate-x-full sm:translate-y-0 max-sm:translate-y-full sm:translate-x-full"
           x-transition:enter-end="translate-x-0 sm:translate-x-0 max-sm:translate-y-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full max-sm:translate-y-full"
           class="fixed z-50 bg-white shadow-dp-card-lg flex flex-col
                  inset-x-0 bottom-0 max-h-[92vh] rounded-t-2xl
                  sm:inset-y-0 sm:right-0 sm:left-auto sm:bottom-auto sm:top-0
                  sm:w-[480px] sm:max-w-full sm:max-h-none sm:rounded-t-none sm:rounded-l-2xl">
        <header class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 id="dp-drawer-title" class="font-display font-semibold text-lg m-0">Dodaj prezent</h2>
            <button type="button" @click="close()" aria-label="Zamknij"
                    class="rounded-dp p-2 hover:bg-slate-100 text-slate-500 transition">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </header>

        <form method="POST" action="{{ route('owner.gifts.store', $tenant) }}"
              enctype="multipart/form-data"
              class="flex-1 overflow-y-auto px-6 py-5"
              @submit="busy = true">
            @csrf

            {{-- URL paste + preview --}}
            <div class="dp-field">
                <label for="dp-url" class="dp-label">Wklej link do produktu</label>
                <div class="relative">
                    <input id="dp-url" name="url" type="url" maxlength="1024" x-model="form.url"
                           @paste="onPaste($event)" @blur="onBlur()"
                           placeholder="https://allegro.pl/oferta/..."
                           class="dp-input pr-9">
                    <span x-show="loading" x-cloak aria-hidden="true"
                          class="absolute right-2.5 top-2.5 inline-block w-4 h-4 border-2 border-dp-purple-300 border-t-dp-purple-600 rounded-full animate-spin"></span>
                </div>
                <p x-show="hint" x-cloak class="text-xs text-dp-muted mt-1" x-text="hint"></p>
            </div>

            <div class="dp-field">
                <label for="dp-title" class="dp-label">Tytuł*</label>
                <input id="dp-title" name="title" type="text" maxlength="120" required
                       x-model="form.title" class="dp-input">
            </div>

            <div class="dp-field grid grid-cols-2 gap-3">
                <div>
                    <label for="dp-price" class="dp-label">Cena (zł)</label>
                    <input id="dp-price" name="price_pln" type="number" step="0.01" min="0"
                           x-model="form.price_pln" class="dp-input">
                </div>
                <div>
                    <label for="dp-priority" class="dp-label">Priorytet</label>
                    <select id="dp-priority" name="priority" x-model="form.priority" class="dp-input">
                        <option value="1">1 — muszę mieć</option>
                        <option value="2">2 — normalny</option>
                        <option value="3">3 — nice to have</option>
                    </select>
                </div>
            </div>

            <div class="dp-field">
                <label for="dp-image" class="dp-label">Zdjęcie (JPG/PNG/WebP, do 4 MB)</label>
                <input id="dp-image" name="image" type="file" accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-dp file:border-0 file:bg-dp-purple-50 file:text-dp-purple-700 file:font-semibold hover:file:bg-dp-purple-100">
                <template x-if="form.image_url">
                    <p class="text-xs text-dp-muted mt-1">
                        Sugerowane zdjęcie ze sklepu:
                        <a :href="form.image_url" target="_blank" class="text-dp-purple-700 underline">otwórz</a>
                        (zapisz lokalnie i wgraj wyżej).
                    </p>
                </template>
            </div>

            <div class="dp-field">
                <label for="dp-description" class="dp-label">Notatka (opcjonalnie)</label>
                <textarea id="dp-description" name="description" rows="3" maxlength="2000"
                          x-model="form.description" class="dp-input"
                          placeholder="Rozmiar L, kolor butelkowa zieleń..."></textarea>
            </div>
        </form>

        <footer class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
            <button type="button" @click="close()" class="dp-btn-secondary">Anuluj</button>
            <button type="submit" form="" onclick="this.closest('aside').querySelector('form').requestSubmit()"
                    :disabled="busy"
                    class="dp-btn-primary px-6">
                <span x-show="! busy">Dodaj do listy</span>
                <span x-show="busy" x-cloak>Dodaję...</span>
            </button>
        </footer>
    </aside>
</div>

<script>
    window.dpGiftDrawer = function (opts) {
        return {
            show: false,
            busy: false,
            loading: false,
            hint: null,
            previewUrl: opts.previewUrl,
            csrf: opts.csrf,
            form: { url: '', title: '', price_pln: '', priority: '2', description: '', image_url: null },

            open() {
                this.show = true;
                // Focus first input after transition.
                this.$nextTick(() => document.getElementById('dp-url')?.focus());
            },
            close() {
                if (this.busy) return;
                this.show = false;
            },
            async onPaste(event) {
                // Defer to next tick so input value is up-to-date.
                await new Promise(r => setTimeout(r, 0));
                this.tryPreview();
            },
            onBlur() {
                if (this.form.url && ! this.form.title) this.tryPreview();
            },
            async tryPreview() {
                const url = this.form.url.trim();
                if (! /^https?:\/\//i.test(url)) { return; }
                this.loading = true; this.hint = null;
                try {
                    const res = await fetch(this.previewUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ url }),
                    });
                    const data = await res.json();
                    if (data.ok && data.preview) {
                        if (data.preview.title && ! this.form.title) this.form.title = data.preview.title;
                        if (data.preview.price_pln_gr && ! this.form.price_pln) {
                            this.form.price_pln = (data.preview.price_pln_gr / 100).toFixed(2);
                        }
                        if (data.preview.description && ! this.form.description) {
                            this.form.description = data.preview.description;
                        }
                        if (data.preview.image_url) this.form.image_url = data.preview.image_url;
                        this.hint = '✓ Pobrano dane z ' + (data.preview.source || 'sklepu') + ' — możesz edytować.';
                    } else if (data.fallback) {
                        this.hint = 'Ten sklep nie obsługuje autopodglądu — wypełnij dane ręcznie.';
                    }
                } catch (e) {
                    this.hint = 'Nie udało się pobrać podglądu — wypełnij dane ręcznie.';
                } finally {
                    this.loading = false;
                }
            },
        };
    };
</script>
