/**
 * DajPrezent.pl — front-end entry.
 *
 *  - Alpine bundled (no more unpkg CDN — CSP can tighten to 'self').
 *  - Hero illustrations are pure SVG/CSS (no Lottie runtime).
 *  - Confetti is the only heavy module and stays code-split.
 */

import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

/**
 * Wire up drag-and-drop reordering for any [data-dp-sortable]
 * container. Each child must carry data-id; on reorder we POST
 * the new order to the URL in data-dp-sortable-url with a JSON
 * body { ids: [...] } and the CSRF token from <meta>.
 *
 * SortableJS is lazy-imported so casual viewers never pay the cost.
 */
const initSortables = async () => {
    const lists = document.querySelectorAll('[data-dp-sortable]');
    if (lists.length === 0) return;
    const { default: Sortable } = await import('sortablejs');
    const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    lists.forEach((el) => {
        const url = el.dataset.dpSortableUrl;
        if (!url) return;
        Sortable.create(el, {
            handle: '[data-dp-sortable-handle]',
            animation: 180,
            ghostClass: 'opacity-40',
            onEnd: () => {
                const ids = Array.from(el.children)
                    .map((row) => Number(row.dataset.id))
                    .filter((n) => Number.isFinite(n) && n > 0);
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ ids }),
                }).catch(() => { /* graceful: order rolls back on next reload */ });
            },
        });
    });
};

if (document.readyState !== 'loading') initSortables();
else document.addEventListener('DOMContentLoaded', initSortables);

window.dpConfetti = async (options = {}) => {
    const { default: confetti } = await import('canvas-confetti');
    return confetti({
        particleCount: 80,
        spread: 70,
        origin: { y: 0.7 },
        colors: ['#4F46E5', '#3B82F6', '#EC4899', '#10B981'],
        ...options,
    });
};

/**
 * Count-up animator. Drives [data-dp-countup="1247"] from 0 → target
 * when the element enters the viewport. Runs once, ~1.4s, easeOut.
 * Respects prefers-reduced-motion (snaps to final value).
 */
const startCountUp = (el) => {
    const target = Number(el.dataset.dpCountup || 0);
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduced || target <= 0) { el.textContent = target.toLocaleString('pl-PL'); return; }
    const dur = 1400;
    const t0 = performance.now();
    const step = (t) => {
        const k = Math.min(1, (t - t0) / dur);
        const eased = 1 - Math.pow(1 - k, 3); // easeOutCubic
        el.textContent = Math.round(target * eased).toLocaleString('pl-PL');
        if (k < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
};

const initObservers = () => {
    if (!('IntersectionObserver' in window)) return;
    const io = new IntersectionObserver((entries) => {
        entries.forEach((e) => {
            if (!e.isIntersecting) return;
            if (e.target.matches('[data-dp-countup]')) startCountUp(e.target);
            if (e.target.matches('.dp-reveal')) e.target.classList.add('dp-revealed');
            io.unobserve(e.target);
        });
    }, { rootMargin: '0px 0px -10% 0px' });

    document.querySelectorAll('[data-dp-countup], .dp-reveal').forEach((el) => io.observe(el));
};

if (document.readyState !== 'loading') initObservers();
else document.addEventListener('DOMContentLoaded', initObservers);

/**
 * Magnetic button — primary CTA tilts a few pixels toward the cursor.
 * Wired via [data-dp-magnet] on any element. Cheap, GPU-only.
 */
document.addEventListener('mousemove', (e) => {
    document.querySelectorAll('[data-dp-magnet]').forEach((el) => {
        const r = el.getBoundingClientRect();
        const dx = e.clientX - (r.left + r.width / 2);
        const dy = e.clientY - (r.top + r.height / 2);
        const dist = Math.hypot(dx, dy);
        if (dist > 140) { el.style.transform = ''; return; }
        const k = (1 - dist / 140) * 8;
        el.style.transform = `translate(${(dx / dist) * k}px, ${(dy / dist) * k}px)`;
    });
});
