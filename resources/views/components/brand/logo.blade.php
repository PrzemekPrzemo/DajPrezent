@props([
    'variant' => 'full',  // full | mono | avatar
    'size' => 'md',       // sm | md | lg
    'tagline' => false,
    'href' => null,
])

@php
    $h = match ($size) { 'sm' => 32, 'lg' => 56, default => 40 };
    $iconSize = (int) round($h * 0.85);
    $textColor = $variant === 'mono' ? '#1E293B' : null;
@endphp

<a href="{{ $href ?? url('/') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 group', 'aria-label' => 'DajPrezent.pl — strona główna']) }}>
    <span class="dp-logo-mark" style="width: {{ $iconSize }}px; height: {{ $iconSize }}px;">
        {{-- Gift box with a heart — extracted from plansze brandingowe. --}}
        <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" class="w-full h-full">
            <defs>
                <linearGradient id="dp-grad-{{ $variant }}" x1="0" y1="0" x2="1" y2="1">
                    @if ($variant === 'mono')
                        <stop offset="0" stop-color="#1E293B"/>
                        <stop offset="1" stop-color="#1E293B"/>
                    @else
                        <stop offset="0" stop-color="#4F46E5"/>
                        <stop offset="1" stop-color="#3B82F6"/>
                    @endif
                </linearGradient>
            </defs>
            {{-- Ribbon top loop --}}
            <path d="M22 14c-3 0-6 2-6 5s3 5 6 5h8s-1-4-2-6c-1-2-3-4-6-4z" fill="url(#dp-grad-{{ $variant }})"/>
            <path d="M42 14c3 0 6 2 6 5s-3 5-6 5h-8s1-4 2-6c1-2 3-4 6-4z" fill="url(#dp-grad-{{ $variant }})"/>
            {{-- Box body --}}
            <rect x="10" y="22" width="44" height="32" rx="4" fill="url(#dp-grad-{{ $variant }})"/>
            {{-- Vertical ribbon stripe --}}
            <rect x="29" y="22" width="6" height="32" fill="#FFFFFF" fill-opacity="0.18"/>
            {{-- Heart in the middle --}}
            <path d="M32 47c-7-5-12-9-12-15a5 5 0 0 1 9-3 5 5 0 0 1 9 3c0 6-5 10-12 15z"
                  fill="#FFFFFF"/>
        </svg>
    </span>

    @if ($variant !== 'avatar')
        <span class="flex flex-col leading-none">
            <span class="font-display font-semibold text-[1.25em]"
                  style="color: {{ $textColor ?? 'inherit' }};">
                <span class="bg-dp-gradient bg-clip-text text-transparent">DajPrezent</span>.pl
            </span>
            @if ($tagline)
                <span class="text-[0.65em] text-dp-muted mt-0.5">Prezenty od serca, bez stresu</span>
            @endif
        </span>
    @endif
</a>
