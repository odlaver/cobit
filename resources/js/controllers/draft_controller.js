import { Controller } from '@hotwired/stimulus';
import { announce } from '../lib/announce';
import { getJson } from '../lib/http';
import { navigate, refresh } from '../lib/navigate';
import { createPoller } from '../lib/poller';
import { play } from '../lib/sfx';

export default class extends Controller {
    static targets = ['timerLabel', 'timerBar'];
    static values = {
        stateUrl: String,
        step: Number,
        turn: Number,
        seconds: Number,
        limit: Number,
        myTurn: Boolean,
        duel: Number,
        action: String,
        lastId: Number,
        lastMine: Boolean,
        lastAuto: Boolean,
        narration: String,
    };

    connect() {
        this.end = Date.now() + this.secondsValue * 1000;
        this.expired = false;
        this.sending = false;
        this.second = null;

        this.cue();
        if (this.secondsValue > 0) this.tick();

        // Giliran lawan dicek lebih sering
        const visible = this.myTurnValue ? 4000 : 1500;
        this.poller = createPoller(() => this.poll(), { visible, hidden: 5000 });
        this.poller.start(visible);
    }

    disconnect() {
        cancelAnimationFrame(this.frame);
        clearTimeout(this.expireTimer);
        this.poller?.stop();
    }

    // Isyarat sekali per langkah baru
    cue() {
        const key = `cobit:draft:${this.duelValue}`;
        const now = `${this.lastIdValue}:${this.stepValue}:${this.turnValue}`;
        let seen = null;

        try {
            seen = sessionStorage.getItem(key);
            sessionStorage.setItem(key, now);
        } catch {
            return;
        }

        if (seen === null || seen === now) return;

        if (this.lastIdValue > 0 && (!this.lastMineValue || this.lastAutoValue)) announce(this.narrationValue);

        if (this.myTurnValue) play('turn');
        else if (this.lastAutoValue) play('auto');
        else if (!this.lastMineValue) play('opponent');
    }

    // Cegah kirim dua kali
    submit(event) {
        if (this.sending) {
            event.preventDefault();
            return;
        }

        this.sending = true;
        play(this.actionValue === 'ban' ? 'ban' : 'pick');
        setTimeout(() => this.element.querySelectorAll('form.js-act button').forEach((button) => (button.disabled = true)));
    }

    tick = () => {
        const left = Math.max(0, (this.end - Date.now()) / 1000);
        const second = Math.ceil(left);

        if (this.hasTimerLabelTarget) this.timerLabelTarget.textContent = second;
        if (this.hasTimerBarTarget) {
            this.timerBarTarget.style.width = `${this.limitValue > 0 ? (left / this.limitValue) * 100 : 0}%`;
            this.timerBarTarget.classList.toggle('bg-red-500', left <= 5);
            this.timerBarTarget.classList.toggle('bg-cyan-500', left > 5);
        }

        // Detik terakhir giliran sendiri
        if (second !== this.second) {
            this.second = second;
            if (this.myTurnValue && !this.sending && second > 0 && second <= 5) play('tick');
        }

        // Waktu habis, server yang memilih acak
        if (left <= 0 && !this.expired) {
            this.expired = true;
            this.expireTimer = setTimeout(refresh, 1000);
            return;
        }

        this.frame = requestAnimationFrame(this.tick);
    };

    async poll() {
        const data = await getJson(this.stateUrlValue);

        if (data.done) {
            navigate(data.redirect);
            return false;
        }

        if (Number(data.step) !== this.stepValue || Number(data.turn_user_id ?? 0) !== this.turnValue) {
            refresh();
            return false;
        }

        this.end = Date.now() + Number(data.seconds_left) * 1000;
    }
}
