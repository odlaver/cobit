import { Controller } from '@hotwired/stimulus';
import { play } from '../lib/sfx';

const SOUND = { win: 'win', lose: 'lose', draw: 'draw' };
const DURATION = 700;
const TIER_DELAY = 900;
const BITS = 18;

// Warna pecahan bit per tier
const BIT_COLORS = {
    God: ['#fbbf24', '#f59e0b', '#22d3ee'],
    Hacker: ['#8b72e6', '#6d4fd6', '#22d3ee'],
    Pro: ['#22d3ee', '#06b6d4', '#8b72e6'],
};

const once = (storage, key) => {
    try {
        if (storage.getItem(key)) return false;
        storage.setItem(key, '1');
        return true;
    } catch {
        return false;
    }
};

// Suara, hitungan naik, dan popup tier hanya sekali
export default class extends Controller {
    static targets = ['number', 'tier', 'tierBar', 'burst'];
    static values = { duel: Number, outcome: String };

    connect() {
        this.reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (once(sessionStorage, `cobit:result:${this.duelValue}`)) {
            play(SOUND[this.outcomeValue]);
            if (!this.reduced) this.countUp();
        }

        if (this.hasTierTarget && once(localStorage, `cobit:tier:${this.duelValue}`)) {
            this.tierTimer = setTimeout(() => this.openTier(), TIER_DELAY);
        }
    }

    disconnect() {
        cancelAnimationFrame(this.frame);
        clearTimeout(this.tierTimer);
    }

    countUp() {
        const items = this.numberTargets.map((el) => ({ el, to: Number(el.dataset.to), sign: el.dataset.sign === 'true' }));
        const start = performance.now();

        const step = (now) => {
            const progress = Math.min(1, (now - start) / DURATION);
            const eased = 1 - (1 - progress) ** 3;

            items.forEach(({ el, to, sign }) => {
                const value = Math.round(to * eased);
                el.textContent = sign && value > 0 ? `+${value}` : String(value).replace('-', '−');
            });

            if (progress < 1) this.frame = requestAnimationFrame(step);
        };

        this.frame = requestAnimationFrame(step);
    }

    openTier() {
        const dialog = this.tierTarget;
        if (typeof dialog.showModal !== 'function' || dialog.open) return;

        dialog.showModal();
        play('tierup');

        if (this.hasTierBarTarget) {
            requestAnimationFrame(() => {
                this.tierBarTarget.style.width = `${this.tierBarTarget.dataset.width}%`;
            });
        }

        if (!this.reduced) this.burst(dialog.dataset.tier);
    }

    closeTier() {
        this.tierTarget.close();
    }

    // Klik di luar kartu menutup popup
    closeTierOutside(event) {
        if (event.target === this.tierTarget) this.tierTarget.close();
    }

    // Pecahan bit dari lambang tier
    burst(tier) {
        if (!this.hasBurstTarget || typeof Element.prototype.animate !== 'function') return;

        const colors = BIT_COLORS[tier] ?? BIT_COLORS.Pro;

        for (let i = 0; i < BITS; i++) {
            const bit = document.createElement('span');
            const size = 6 + Math.random() * 6;
            const angle = (Math.PI * 2 * i) / BITS + Math.random() * 0.4;
            const distance = 70 + Math.random() * 60;

            bit.style.cssText = `position:absolute;left:50%;top:50%;width:${size}px;height:${size}px;margin:${-size / 2}px;border-radius:2px;background:${colors[i % colors.length]}`;
            this.burstTarget.append(bit);

            bit.animate(
                [
                    { transform: 'translate(0, 0) rotate(0deg) scale(1)', opacity: 1 },
                    { transform: `translate(${Math.cos(angle) * distance}px, ${Math.sin(angle) * distance}px) rotate(${Math.random() * 360}deg) scale(0.6)`, opacity: 0 },
                ],
                { duration: 900 + Math.random() * 300, delay: 150, easing: 'cubic-bezier(0.2, 0.7, 0.3, 1)', fill: 'both' },
            ).finished.then(() => bit.remove()).catch(() => bit.remove());
        }
    }
}
