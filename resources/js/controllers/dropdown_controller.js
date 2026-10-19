import { Controller } from '@hotwired/stimulus';

// Menu popover yang bebas dari tabel
export default class extends Controller {
    static targets = ['button', 'menu'];

    connect() {
        this.menuTarget.addEventListener('toggle', this.onToggle);
    }

    disconnect() {
        this.menuTarget.removeEventListener('toggle', this.onToggle);
        this.unbind();
    }

    onToggle = (event) => {
        const open = event.newState === 'open';
        this.buttonTarget.setAttribute('aria-expanded', String(open));

        if (open) {
            this.place();
            window.addEventListener('scroll', this.place, true);
            window.addEventListener('resize', this.place);
        } else {
            this.unbind();
        }
    };

    // Tempel di bawah tombol, tetap di layar
    place = () => {
        const anchor = this.buttonTarget.getBoundingClientRect();
        const menu = this.menuTarget;
        const gap = 6;
        const width = menu.offsetWidth;
        const height = menu.offsetHeight;

        let left = anchor.right - width;
        left = Math.max(8, Math.min(left, window.innerWidth - width - 8));

        let top = anchor.bottom + gap;
        if (top + height > window.innerHeight - 8) top = Math.max(8, anchor.top - height - gap);

        menu.style.left = `${left}px`;
        menu.style.top = `${top}px`;
    };

    unbind() {
        window.removeEventListener('scroll', this.place, true);
        window.removeEventListener('resize', this.place);
    }
}
