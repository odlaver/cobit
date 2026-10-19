import { Controller } from '@hotwired/stimulus';
import { announce } from '../lib/announce';
import { getJson } from '../lib/http';
import { navigate } from '../lib/navigate';
import { createPoller } from '../lib/poller';
import { play } from '../lib/sfx';

export default class extends Controller {
    static targets = ['form', 'timerBar', 'timerLabel', 'opponentProgress'];
    static values = { stateUrl: String, remaining: Number, limit: Number };

    connect() {
        this.end = Date.now() + this.remainingValue * 1000;
        this.sent = false;
        this.timedOut = false;
        this.second = null;
        this.opponentDone = false;

        play('blip');
        document.addEventListener('keydown', this.onKey);
        this.tick();

        this.poller = createPoller(() => this.poll(), { visible: 4000, hidden: 10000 });
        this.poller.start(4000);
    }

    disconnect() {
        document.removeEventListener('keydown', this.onKey);
        cancelAnimationFrame(this.frame);
        this.poller?.stop();
    }

    // Kunci form setelah terkirim
    submit(event) {
        if (this.sent) {
            event.preventDefault();
            return;
        }

        this.sent = true;
        if (!this.timedOut) play('select');
        this.formTarget.querySelectorAll('button').forEach((button) => button.setAttribute('aria-disabled', 'true'));
    }

    // Tombol A-D di keyboard
    onKey = (event) => {
        if (event.ctrlKey || event.metaKey || event.altKey || event.repeat) return;
        if (event.target.closest('input, textarea, select')) return;

        this.formTarget.querySelector(`[data-key="${event.key.toLowerCase()}"]`)?.click();
    };

    tick = () => {
        const left = Math.max(0, (this.end - Date.now()) / 1000);
        const second = Math.ceil(left);

        this.timerBarTarget.style.width = `${(left / this.limitValue) * 100}%`;
        this.timerLabelTarget.textContent = second;
        this.timerBarTarget.classList.toggle('bg-red-500', left <= 5);
        this.timerBarTarget.classList.toggle('bg-cyan-500', left > 5);

        if (second !== this.second) {
            this.second = second;
            if (!this.sent && second > 0 && second <= 5) play('tick');
        }

        if (left <= 0) {
            if (!this.sent) {
                this.timedOut = true;
                play('timeout');
                this.formTarget.requestSubmit();
            }
            return;
        }

        this.frame = requestAnimationFrame(this.tick);
    };

    // Pantau progres lawan
    async poll() {
        const data = await getJson(this.stateUrlValue);

        if (data.done) {
            navigate(data.redirect);
            return false;
        }

        if (this.hasOpponentProgressTarget) this.opponentProgressTarget.textContent = data.opponent;

        if (!this.opponentDone && data.total > 0 && data.opponent >= data.total) {
            this.opponentDone = true;
            play('opponent');
            announce('Lawanmu sudah menjawab semua soal.');
        }
    }
}
