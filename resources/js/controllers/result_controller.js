import { Controller } from '@hotwired/stimulus';
import { play } from '../lib/sfx';

const SOUND = { win: 'win', lose: 'lose', draw: 'draw' };
const DURATION = 700;

// Suara dan hitungan naik hanya sekali
export default class extends Controller {
    static targets = ['number'];
    static values = { duel: Number, outcome: String };

    connect() {
        const key = `cobit:result:${this.duelValue}`;

        try {
            if (sessionStorage.getItem(key)) return;
            sessionStorage.setItem(key, '1');
        } catch {
            return;
        }

        play(SOUND[this.outcomeValue]);

        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) this.countUp();
    }

    disconnect() {
        cancelAnimationFrame(this.frame);
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
}
