/**
 * DajPrezent.pl — front-end entry.
 *
 *  - Alpine bundled (no more unpkg CDN — CSP can tighten to 'self').
 *  - Lottie + confetti are loaded on demand so the base bundle stays slim.
 */

import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

window.dpLottie = async (target, animationData, options = {}) => {
    const { default: lottie } = await import('lottie-web');
    return lottie.loadAnimation({
        container: target,
        renderer: 'svg',
        loop: true,
        autoplay: true,
        animationData,
        ...options,
    });
};

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
