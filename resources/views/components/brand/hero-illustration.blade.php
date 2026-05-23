{{--
  Pure-SVG hero illustration — replaces the dropped Lottie animation.

  Layered motion (all CSS keyframes, no JS):
    - Whole box gently floats up/down (dp-float, 6s).
    - The lid rocks side-to-side as if the ribbon is loosening (dp-lid-rock, 4.5s).
    - The heart on the lid pulses (dp-heart-pulse-loop, 1.6s).
    - Three sparkle stars orbit-twinkle around the box (dp-sparkle-{a,b,c}, staggered).

  Decorative — `aria-hidden="true"` so screen readers skip it.
--}}
<svg viewBox="0 0 280 280" xmlns="http://www.w3.org/2000/svg"
     class="w-full h-auto" aria-hidden="true">
    <defs>
        <linearGradient id="dp-gift-grad" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%"  stop-color="#4F46E5"/>
            <stop offset="100%" stop-color="#3B82F6"/>
        </linearGradient>
        <linearGradient id="dp-ribbon-grad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%"  stop-color="#EC4899"/>
            <stop offset="100%" stop-color="#DB2777"/>
        </linearGradient>
        <radialGradient id="dp-glow" cx="50%" cy="50%" r="50%">
            <stop offset="0%"  stop-color="#A78BFA" stop-opacity="0.55"/>
            <stop offset="100%" stop-color="#A78BFA" stop-opacity="0"/>
        </radialGradient>
        <filter id="dp-soft-shadow" x="-20%" y="-20%" width="140%" height="140%">
            <feGaussianBlur stdDeviation="4"/>
        </filter>
    </defs>

    {{-- Ambient glow halo --}}
    <circle cx="140" cy="150" r="120" fill="url(#dp-glow)"/>

    {{-- Three orbiting sparkles --}}
    <g class="dp-spark dp-spark-a">
        <path d="M 30 60 l 4 -10 l 4 10 l 10 4 l -10 4 l -4 10 l -4 -10 l -10 -4 z" fill="#F5F3FF"/>
    </g>
    <g class="dp-spark dp-spark-b">
        <path d="M 232 90 l 3 -8 l 3 8 l 8 3 l -8 3 l -3 8 l -3 -8 l -8 -3 z" fill="#FCE7F3"/>
    </g>
    <g class="dp-spark dp-spark-c">
        <path d="M 220 220 l 2 -6 l 2 6 l 6 2 l -6 2 l -2 6 l -2 -6 l -6 -2 z" fill="#EFF6FF"/>
    </g>

    {{-- Floating gift box (whole group bobs) --}}
    <g class="dp-gift-float" style="transform-origin: 140px 160px;">
        {{-- Soft drop-shadow under box --}}
        <ellipse cx="140" cy="246" rx="78" ry="9" fill="#1E293B" opacity="0.12" filter="url(#dp-soft-shadow)"/>

        {{-- Box body --}}
        <rect x="60" y="120" width="160" height="120" rx="16" fill="url(#dp-gift-grad)"/>
        {{-- Vertical ribbon on body --}}
        <rect x="128" y="120" width="24" height="120" fill="url(#dp-ribbon-grad)"/>

        {{-- Lid (rocks slightly via its own keyframe) --}}
        <g class="dp-lid-rock" style="transform-origin: 140px 130px;">
            <rect x="50" y="100" width="180" height="38" rx="10" fill="url(#dp-gift-grad)"/>
            <rect x="128" y="100" width="24" height="38" fill="url(#dp-ribbon-grad)"/>
            {{-- Bow loops --}}
            <ellipse cx="118" cy="92" rx="16" ry="12" fill="url(#dp-ribbon-grad)"/>
            <ellipse cx="162" cy="92" rx="16" ry="12" fill="url(#dp-ribbon-grad)"/>
            <circle cx="140" cy="92" r="6" fill="#BE185D"/>
        </g>

        {{-- Pulsing heart in the center of the body --}}
        <g class="dp-heart-loop" style="transform-origin: 140px 180px;">
            <path d="M 140 200 c -16 -16 -28 -22 -28 -34 c 0 -8 6 -14 14 -14 c 6 0 11 4 14 8 c 3 -4 8 -8 14 -8 c 8 0 14 6 14 14 c 0 12 -12 18 -28 34 z"
                  fill="#FFFFFF" opacity="0.95"/>
        </g>
    </g>

    <style>
        @keyframes dp-float {
            0%, 100% { transform: translateY(0) rotate(-1deg); }
            50%      { transform: translateY(-8px) rotate(1deg); }
        }
        @keyframes dp-lid-rock {
            0%, 100% { transform: rotate(-2deg); }
            50%      { transform: rotate(2deg); }
        }
        @keyframes dp-heart-loop {
            0%, 100% { transform: scale(1); }
            30%      { transform: scale(1.18); }
            60%      { transform: scale(0.96); }
        }
        @keyframes dp-spark {
            0%, 100% { opacity: 0.2; transform: scale(0.7) rotate(0deg); }
            50%      { opacity: 1;   transform: scale(1.1) rotate(180deg); }
        }
        .dp-gift-float { animation: dp-float 6s ease-in-out infinite; }
        .dp-lid-rock   { animation: dp-lid-rock 4.5s ease-in-out infinite; }
        .dp-heart-loop { animation: dp-heart-loop 1.6s ease-in-out infinite; }
        .dp-spark      { animation: dp-spark 3s ease-in-out infinite; transform-box: fill-box; transform-origin: center; }
        .dp-spark-b    { animation-delay: -1s; animation-duration: 3.6s; }
        .dp-spark-c    { animation-delay: -2s; animation-duration: 4.2s; }

        @media (prefers-reduced-motion: reduce) {
            .dp-gift-float, .dp-lid-rock, .dp-heart-loop, .dp-spark { animation: none; }
        }
    </style>
</svg>
