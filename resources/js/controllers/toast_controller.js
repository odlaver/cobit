import { Controller } from '@hotwired/stimulus';

let escapeBound = false;

// Esc menutup notifikasi terbaru
function bindEscape() {
    if (escapeBound) return;
    escapeBound = true;

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || event.defaultPrevented) return;

        try {
            if (document.querySelector(':popover-open')) return;
        } catch {
            // Browser lama tanpa :popover-open
        }

        document.getElementById('toast-root')?.lastElementChild?.remove();
    });
}

export default class extends Controller {
    static values = { sticky: Boolean, delay: { type: Number, default: 5000 } };

    connect() {
        bindEscape();

        // Flash pindah ke wadah live region
        const root = document.getElementById('toast-root');
        if (root && this.element.parentElement !== root) {
            this.element.dataset.toastFlash = '';
            root.append(this.element);
            return;
        }

        this.element.addEventListener('mouseenter', this.pause);
        this.element.addEventListener('focusin', this.pause);
        this.element.addEventListener('mouseleave', this.resume);
        this.element.addEventListener('focusout', this.resume);
        this.resume();
        this.enter();
    }

    disconnect() {
        clearTimeout(this.timer);
        this.element.removeEventListener('mouseenter', this.pause);
        this.element.removeEventListener('focusin', this.pause);
        this.element.removeEventListener('mouseleave', this.resume);
        this.element.removeEventListener('focusout', this.resume);
    }

    close() {
        this.element.remove();
    }

    pause = () => clearTimeout(this.timer);

    resume = () => {
        clearTimeout(this.timer);
        if (!this.stickyValue) this.timer = setTimeout(() => this.close(), this.delayValue);
    };

    // Muncul halus dari bawah
    enter() {
        const { classList } = this.element;
        classList.add('transition', 'duration-200', 'ease-out', 'opacity-0', 'translate-y-2');
        requestAnimationFrame(() => requestAnimationFrame(() => classList.remove('opacity-0', 'translate-y-2')));
    }
}
