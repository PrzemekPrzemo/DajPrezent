@extends('layouts.panel')

@section('title', __('messages.owner.dashboard_h1'))

@section('content')
    <header class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="font-display text-2xl sm:text-3xl font-bold m-0">{{ __('messages.owner.dashboard_h1') }}</h1>
        <div class="flex flex-wrap gap-2">
            @if ($siblingSlots !== null && $siblingSlots['free'] > 0)
                <button type="button" @click="$dispatch('open-add-list')"
                        class="dp-btn-primary">
                    {{ __('messages.owner.add_another_list') }}
                    <span class="ml-1 text-xs opacity-90">{{ __('messages.owner.slots_free', ['free' => $siblingSlots['free'], 'limit' => $siblingSlots['limit']]) }}</span>
                </button>
            @endif
            <a href="{{ route('owner.invoices.index') }}" class="dp-btn-secondary">{{ __('messages.owner.invoices') }}</a>
            <a href="{{ route('owner.bookmarklet.show') }}" class="dp-btn-secondary">{{ __('messages.owner.bookmarklet') }}</a>
        </div>
    </header>

    @if ($siblingSlots !== null && $siblingSlots['free'] > 0)
        <div x-data="{ open: false }"
             x-on:open-add-list.window="open = true; $nextTick(() => $refs.nameField?.focus())"
             x-show="open" x-cloak x-transition
             class="dp-card mb-4 ring-2 ring-dp-purple-200">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-display font-semibold text-lg m-0">{{ __('messages.owner.add_in_package', ['pkg' => $siblingSlots['package']]) }}</h2>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-700" aria-label="{{ __('messages.common.close') }}">✕</button>
            </div>
            <form method="POST" action="{{ route('owner.lists.store') }}" class="grid sm:grid-cols-[1fr,1fr,auto] gap-3 items-end">
                @csrf
                <div class="dp-field">
                    <label class="dp-label" for="add-list-name">{{ __('messages.owner.add_list_name') }}</label>
                    <input id="add-list-name" name="name" x-ref="nameField" required maxlength="80"
                           class="dp-input" placeholder="{{ __('messages.owner.add_list_name_placeholder') }}">
                </div>
                <div class="dp-field">
                    <label class="dp-label" for="add-list-slug">{{ __('messages.owner.add_list_address') }}</label>
                    <div class="flex items-center gap-1 text-sm">
                        <span class="text-dp-muted">dajprezent.pl/</span>
                        <input id="add-list-slug" name="slug" required maxlength="40" pattern="[a-z0-9][a-z0-9\-]{0,38}[a-z0-9]"
                               class="dp-input" placeholder="ania-urodziny">
                    </div>
                </div>
                <button type="submit" class="dp-btn-primary h-fit">{{ __('messages.owner.add_list_submit') }}</button>
            </form>
            <p class="text-xs text-dp-muted mt-2">
                {{ __('messages.owner.add_list_inherits', ['pkg' => $siblingSlots['package']]) }}
            </p>
        </div>
    @endif

    @if (session('status'))
        <div role="status" class="bg-emerald-50 text-emerald-800 rounded-dp px-4 py-3 text-sm mb-4">{{ session('status') }}</div>
    @endif

    @if ($tenants->isEmpty())
        <div class="dp-card text-center py-10">
            <div class="w-20 h-20 mx-auto mb-4 rounded-dp-lg bg-dp-gradient flex items-center justify-center text-white text-3xl">🎁</div>
            <h3 class="font-display font-semibold text-lg mb-1">{{ __('messages.owner.empty_h1') }}</h3>
            <p class="text-sm text-dp-muted max-w-md mx-auto mb-5">{{ __('messages.owner.empty_lead') }}</p>
            <a href="{{ route('public.pricing') }}" class="dp-btn-primary px-6 py-3">{{ __('messages.owner.empty_cta') }}</a>
        </div>
    @else
        @foreach ($tenants as $tenant)
            @php
                $total = (int) $tenant->gifts_total;
                $taken = (int) $tenant->gifts_reserved + (int) $tenant->gifts_received;
                $progress = $total > 0 ? (int) round($taken * 100 / $total) : 0;

                $daysLeft = $tenant->expires_at
                    ? (int) floor(($tenant->expires_at->timestamp - now()->timestamp) / 86400)
                    : null;
                $expiryClass = match (true) {
                    $daysLeft === null => 'bg-slate-100 text-slate-600',
                    $daysLeft < 0      => 'bg-slate-700 text-white',
                    $daysLeft < 14     => 'bg-red-50 text-red-700',
                    $daysLeft < 30     => 'bg-amber-50 text-amber-800',
                    default            => 'bg-emerald-50 text-emerald-700',
                };
                $publicUrl = url('/'.$tenant->slug);

                // First-run heuristic: brand-new tenant with zero gifts gets
                // the onboarding tour modal. Owner can dismiss; localStorage
                // remembers per tenant.
                $isFreshTenant = $total === 0;
            @endphp
            <div class="dp-card mt-4"
                 @if ($isFreshTenant)
                     x-data="dpTour({ key: 'dp.tour.{{ $tenant->id }}' })"
                     x-init="maybeOpen()"
                 @else
                     x-data="{}"
                 @endif>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex-1 min-w-[240px]">
                        <h2 class="font-display text-xl font-semibold m-0">{{ $tenant->name }}</h2>
                        <div class="text-sm text-dp-muted mt-1">
                            <a href="{{ $publicUrl }}" target="_blank"
                               class="text-dp-purple-700 hover:underline">dajprezent.pl/{{ $tenant->slug }}</a>
                            @if ($tenant->expires_at)
                                ·
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $expiryClass }}">
                                    @if ($daysLeft === null)         {{ __('messages.owner.expiry_no_term') }}
                                    @elseif ($daysLeft < 0)          {{ __('messages.owner.expiry_past', ['n' => abs($daysLeft)]) }}
                                    @elseif ($daysLeft === 0)        {{ __('messages.owner.expiry_today') }}
                                    @elseif ($daysLeft < 14)         {{ __('messages.owner.expiry_soon', ['n' => $daysLeft]) }}
                                    @else                            {{ __('messages.owner.expiry_valid', ['date' => $tenant->expires_at->translatedFormat('j F Y')]) }}
                                    @endif
                                </span>
                            @endif
                        </div>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <span class="dp-chip dp-chip-available">{{ __('messages.owner.gifts_count', ['n' => $total]) }}</span>
                            <span class="dp-chip dp-chip-reserved">{{ __('messages.owner.gifts_reserved', ['n' => $tenant->gifts_reserved]) }}</span>
                            <span class="dp-chip dp-chip-received">{{ __('messages.owner.gifts_received', ['n' => $tenant->gifts_received]) }}</span>
                        </div>

                        @if ($total > 0)
                            <div class="mt-3">
                                <div class="bg-slate-100 rounded-full overflow-hidden h-2">
                                    <div class="h-full bg-dp-gradient transition-all duration-300" style="width: {{ $progress }}%"></div>
                                </div>
                                <div class="text-xs text-dp-muted mt-1">{{ __('messages.owner.progress_label', ['n' => $progress]) }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-col gap-2">
                        <a href="{{ route('owner.gifts.index', $tenant) }}" class="dp-btn-primary text-center">{{ __('messages.owner.manage_gifts') }}</a>
                        @if (in_array($tenant->kind, ['wedding_basic', 'wedding_premium'], true))
                            <a href="{{ route('owner.wedding.edit', $tenant) }}" class="dp-btn-secondary text-center">{{ __('messages.owner.manage_wedding') }}</a>
                        @endif
                        <button type="button" @click="$dispatch('open-share-' + {{ $tenant->id }})"
                                class="dp-btn-secondary">{{ __('messages.owner.share_list') }}</button>
                        <a href="{{ route('owner.tenant.settings.edit', $tenant) }}" class="dp-btn-ghost text-center">{{ __('messages.owner.settings') }}</a>
                    </div>
                </div>

                {{-- SHARE WIDGET: QR + copy + WhatsApp/Messenger/email --}}
                <div x-data="{ open: false, copied: false }"
                     x-on:open-share-{{ $tenant->id }}.window="open = true"
                     x-show="open" x-cloak x-transition
                     class="mt-4 pt-4 border-t border-slate-100 grid sm:grid-cols-[180px,1fr] gap-4 items-start">
                    <img src="{{ route('owner.qr', $tenant) }}"
                         alt="QR — {{ $tenant->name }}"
                         class="w-44 h-44 rounded-dp border border-slate-100 bg-white p-2">
                    <div class="space-y-3">
                        <p class="text-sm text-dp-muted m-0">{{ __('messages.share.lead') }}</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button"
                                    @click="navigator.clipboard.writeText({{ Js::from($publicUrl) }}).then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                                    class="dp-btn-secondary">
                                <span x-show="! copied">{{ __('messages.share.copy_link') }}</span>
                                <span x-show="copied" x-cloak class="text-emerald-700">{{ __('messages.share.copied') }}</span>
                            </button>
                            <a href="https://wa.me/?text={{ rawurlencode(__('messages.share.share_text').$publicUrl) }}"
                               target="_blank" rel="noopener" class="dp-btn-secondary">{{ __('messages.share.whatsapp') }}</a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($publicUrl) }}"
                               target="_blank" rel="noopener" class="dp-btn-secondary">{{ __('messages.share.messenger') }}</a>
                            <a href="mailto:?subject={{ rawurlencode(__('messages.share.share_subject')) }}&body={{ rawurlencode(__('messages.share.share_text').$publicUrl) }}"
                               class="dp-btn-secondary">{{ __('messages.share.email') }}</a>
                            <a href="{{ route('owner.qr', $tenant) }}" download="dajprezent-{{ $tenant->slug }}-qr.svg"
                               class="dp-btn-ghost">{{ __('messages.share.download_qr') }}</a>
                        </div>
                        <p class="text-xs text-dp-muted m-0">{{ __('messages.share.qr_hint') }}</p>
                    </div>
                </div>

                {{-- ONBOARDING TOUR (tylko gdy lista jest świeża i nieprzeczytana) --}}
                @if ($isFreshTenant)
                    <template x-teleport="body">
                        <div x-show="show" x-cloak x-transition.opacity
                             class="fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4"
                             @click.self="close()"
                             role="dialog" aria-modal="true">
                            <div class="dp-card max-w-md w-full p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                        <template x-for="i in 3" :key="i">
                                            <span class="w-2.5 h-2.5 rounded-full" :class="step === i ? 'bg-dp-purple-600' : 'bg-slate-200'"></span>
                                        </template>
                                    </div>
                                    <button type="button" @click="close()" class="text-slate-400 hover:text-slate-600" aria-label="{{ __('messages.common.close') }}">✕</button>
                                </div>
                                <div x-show="step === 1">
                                    <h3 class="font-display font-semibold text-lg mb-2">{{ __('messages.tour.step1_h', ['name' => $tenant->name]) }}</h3>
                                    <p class="text-sm text-dp-muted">{{ __('messages.tour.step1_body') }}</p>
                                </div>
                                <div x-show="step === 2" x-cloak>
                                    <h3 class="font-display font-semibold text-lg mb-2">{{ __('messages.tour.step2_h') }}</h3>
                                    <p class="text-sm text-dp-muted">{{ __('messages.tour.step2_body') }}</p>
                                </div>
                                <div x-show="step === 3" x-cloak>
                                    <h3 class="font-display font-semibold text-lg mb-2">{{ __('messages.tour.step3_h') }}</h3>
                                    <p class="text-sm text-dp-muted">{{ __('messages.tour.step3_body') }}</p>
                                    <p class="text-sm text-dp-muted mt-2">{!! __('messages.tour.step3_promise') !!}</p>
                                </div>
                                <div class="flex justify-between mt-5">
                                    <button type="button" @click="close()" class="dp-btn-ghost">{{ __('messages.tour.skip') }}</button>
                                    <button type="button"
                                            @click="step < 3 ? step++ : close()"
                                            class="dp-btn-primary px-5">
                                        <span x-text="step < 3 ? {{ Js::from(__('messages.tour.next')) }} : {{ Js::from(__('messages.tour.start')) }}"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                @endif
            </div>
        @endforeach
    @endif

    <script>
        window.dpTour = function ({ key }) {
            return {
                show: false, step: 1, key,
                maybeOpen() {
                    try { if (localStorage.getItem(this.key) === '1') return; } catch (e) {}
                    this.show = true;
                },
                close() {
                    this.show = false;
                    try { localStorage.setItem(this.key, '1'); } catch (e) {}
                },
            };
        };
    </script>
@endsection
