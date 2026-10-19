import { Controller } from '@hotwired/stimulus';
import { announce } from '../lib/announce';
import { navigate } from '../lib/navigate';
import { play } from '../lib/sfx';

// Hitung mundur sebelum soal pertama
export default class extends Controller {
    static targets = ['count'];
    static values = { seconds: Number, url: String, announce: String };

    connect() {
        this.end = Date.now() + this.secondsValue * 1000;
        this.shown = null;
        announce(this.announceValue);
        this.tick();
    }

    disconnect() {
        cancelAnimationFrame(this.frame);
        clearTimeout(this.timer);
    }

    tick = () => {
        const left = Math.max(0, (this.end - Date.now()) / 1000);
        const shown = Math.ceil(left);

        if (shown !== this.shown && shown > 0) {
            this.shown = shown;
            this.countTarget.textContent = shown;
            play('count');
        }

        if (left <= 0) {
            this.countTarget.textContent = 'Mulai';
            play('go');
            this.timer = setTimeout(() => navigate(this.urlValue), 250);
            return;
        }

        this.frame = requestAnimationFrame(this.tick);
    };
}
