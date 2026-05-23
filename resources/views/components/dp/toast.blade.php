{{--
  Unified flash + confetti surface for both panel & public layouts.

  Renders any of these session keys as a small toast in the bottom-right:
    - `status`        green success toast
    - `rsvp_status`   green RSVP-thanks toast
    - `flash_error`   red error toast (validation errors still render inline)

  Additionally — if the controller put `dp_confetti => <reason>` into the
  session, the helper fires window.dpConfetti() once on mount. Reason is
  free-form (`first-gift`, `first-rsvp`) so we could analytics-tag later.
--}}
@php
    $messages = array_filter([
        ['kind' => 'ok',  'text' => session('status')],
        ['kind' => 'ok',  'text' => session('rsvp_status')],
        ['kind' => 'err', 'text' => session('flash_error')],
    ], fn ($m) => ! empty($m['text']));
    $confettiReason = session('dp_confetti');
@endphp

@if (! empty($messages) || $confettiReason)
    <div x-data="{
            toasts: @js($messages),
            init() {
                @if ($confettiReason)
                    window.dpConfetti && window.dpConfetti();
                @endif
                // Auto-dismiss after 4s — long enough to read, short enough not to nag.
                this.toasts.forEach((_, i) => setTimeout(() => { this.toasts.splice(i, 1); }, 4000));
            }
         }"
         class="fixed bottom-4 right-4 z-50 space-y-2 max-w-sm">
        <template x-for="(t, idx) in toasts" :key="idx">
            <div x-show="true" x-transition.opacity
                 :class="t.kind === 'ok' ? 'bg-emerald-600' : 'bg-red-600'"
                 class="text-white px-4 py-3 rounded-dp shadow-dp-card-lg text-sm font-medium flex items-start gap-3">
                <span x-text="t.kind === 'ok' ? '✓' : '!'" class="font-bold"></span>
                <span x-text="t.text" class="flex-1"></span>
                <button @click="toasts.splice(idx, 1)" class="opacity-70 hover:opacity-100" aria-label="Zamknij">×</button>
            </div>
        </template>
    </div>
@endif
