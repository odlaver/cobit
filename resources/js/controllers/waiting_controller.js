import { Controller } from '@hotwired/stimulus';
import { getJson } from '../lib/http';
import { navigate } from '../lib/navigate';
import { createPoller } from '../lib/poller';
import { play } from '../lib/sfx';

export default class extends Controller {
    static targets = ['status', 'strip'];
    static values = { stateUrl: String };

    connect() {
        this.poller = createPoller(() => this.poll(), { visible: 2000, hidden: 5000 });
        this.poller.start(2000);
    }

    disconnect() {
        this.poller?.stop();
    }

    async poll() {
        let data;

        try {
            data = await getJson(this.stateUrlValue);
        } catch (error) {
            this.statusTarget.textContent = 'Koneksi terputus, mencoba lagi...';
            throw error;
        }

        // Ronde selesai
        if (data.done) {
            play('done');
            navigate(data.redirect);
            return false;
        }

        this.statusTarget.textContent = `${data.opponent} dari ${data.total} soal terjawab`;
        this.stripTarget.querySelectorAll('li').forEach((cell, i) => {
            cell.classList.toggle('bg-ungu-500', i < data.opponent);
            cell.classList.toggle('bg-navy-100', i >= data.opponent);
        });
    }
}
