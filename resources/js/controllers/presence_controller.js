import { Controller } from '@hotwired/stimulus';
import { getJson } from '../lib/http';
import { createPoller } from '../lib/poller';

const TEXT = { online: 'Online', dueling: 'Sedang berduel', offline: 'Offline' };

// Perbarui titik dan label status tanpa reload
export default class extends Controller {
    static values = { frame: String };

    connect() {
        this.known = null;
        this.poller = createPoller(() => this.poll(), { visible: 15000, hidden: 60000 });
        this.poller.start(15000);
    }

    disconnect() {
        this.poller?.stop();
    }

    async poll() {
        const url = document.body.dataset.presenceUrl;
        if (!url) return false;

        const { online, dueling } = await getJson(url);
        const on = new Set(online.map(String));
        const busy = new Set(dueling.map(String));
        const stateOf = (id) => (busy.has(id) ? 'dueling' : on.has(id) ? 'online' : 'offline');

        this.element.querySelectorAll('[data-presence-dot]').forEach((dot) => this.apply(dot, stateOf(dot.dataset.presenceDot)));

        this.element.querySelectorAll('[data-presence-label]').forEach((label) => {
            const state = stateOf(label.dataset.presenceLabel);
            this.apply(label, 'basic' in label.dataset && state === 'dueling' ? 'online' : state);
        });

        this.element.querySelectorAll('[data-presence-note]').forEach((note) => {
            note.hidden = stateOf(note.dataset.presenceNote) !== 'offline';
        });

        // Daftar online berubah, muat ulang frame
        const signature = `${[...on].sort().join(',')}|${[...busy].sort().join(',')}`;
        const frame = this.hasFrameValue ? document.getElementById(this.frameValue) : null;
        if (frame && this.known !== null && this.known !== signature) {
            if (frame.src) frame.reload();
            else frame.src = frame.dataset.src;
        }
        this.known = signature;
    }

    apply(element, state) {
        if (element.dataset.state === state) return;

        element.dataset.state = state;
        const text = element.querySelector('[data-presence-text]');
        if (text) text.textContent = TEXT[state];
    }
}
