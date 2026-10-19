import { getJson } from '../lib/http';
import { navigate, refresh } from '../lib/navigate';
import { createPoller } from '../lib/poller';
import { play } from '../lib/sfx';

// Halaman daftar tantangan ikut disegarkan
const LIST_PAGES = /^\/challenges(\/lawan)?\/?$/;
const SOUND = { incoming: 'notify', accepted: 'match' };

let poller = null;
let heartbeat = null;
let baseline = null;
let ownerId = null;
let inbox = 0;

const body = () => document.body.dataset;
const seenKey = () => `cobit:toasts:${ownerId}`;

function loadSeen() {
    try {
        return new Set(JSON.parse(sessionStorage.getItem(seenKey()) || '[]'));
    } catch {
        return new Set();
    }
}

function saveSeen(seen) {
    try {
        sessionStorage.setItem(seenKey(), JSON.stringify([...seen].slice(-100)));
    } catch {
        // Penyimpanan diblokir
    }
}

// Banyak tab, cukup satu yang berbunyi
function ringOnce(id, sound) {
    try {
        const key = `cobit:rang:${id}`;
        if (localStorage.getItem(key)) return;
        localStorage.setItem(key, String(Date.now()));
    } catch {
        // Tetap bunyikan
    }

    play(sound);
}

function showToasts(toasts, first) {
    const root = document.getElementById('toast-root');
    if (!root) return;

    const live = new Set(toasts.map((toast) => toast.id));
    const seen = loadSeen();

    // Tantangan yang sudah dijawab lewat tempat lain
    root.querySelectorAll('[data-toast-id^="incoming-"]').forEach((el) => {
        if (!live.has(el.dataset.toastId)) el.remove();
    });

    for (const toast of toasts) {
        if (seen.has(toast.id)) continue;
        seen.add(toast.id);

        // Muat pertama: hanya kabar yang baru sekali
        if (first && !toast.fresh) continue;

        root.insertAdjacentHTML('beforeend', toast.html);
        root.lastElementChild.dataset.toastId = toast.id;
        ringOnce(toast.id, SOUND[toast.kind] ?? 'opponent');
    }

    saveSeen(seen);
}

function setInbox(count) {
    inbox = count;

    document.querySelectorAll('[data-inbox-badge]').forEach((badge) => {
        badge.hidden = count === 0;
        badge.querySelector('[data-inbox-count]').textContent = count;
    });

    applyTitle();
}

function applyTitle() {
    const base = document.title.replace(/^\(\d+\) /, '');
    document.title = inbox > 0 ? `(${inbox}) ${base}` : base;
}

async function check() {
    const url = body().statusUrl;
    if (!url) return false;

    const data = await getJson(url);
    const previous = baseline;
    baseline = data;

    setInbox(data.inbox);
    showToasts(data.toasts ?? [], previous === null);

    // Tantangan diterima, masuk duel
    if (data.duel && data.duel !== previous?.duel) {
        poller = null;
        navigate(data.redirect);
        return false;
    }

    if (previous && (data.inbox !== previous.inbox || data.sent !== previous.sent) && LIST_PAGES.test(window.location.pathname)) {
        refresh();
    }
}

function stopAll() {
    poller?.stop();
    heartbeat?.stop();
    poller = null;
    heartbeat = null;
}

function sync() {
    const { role, duel, userId } = body();

    if (userId !== ownerId) {
        stopAll();
        baseline = null;
        inbox = 0;
        ownerId = userId;
    }

    if (!userId) return;

    // Admin dan asdos cukup tanda aktif
    if (role !== 'mahasiswa') {
        if (!heartbeat) {
            heartbeat = createPoller(() => getJson(body().presenceUrl), { visible: 60000, hidden: 120000 });
            heartbeat.start(60000);
        }
        return;
    }

    applyTitle();

    // Selama duel, halaman game yang memantau
    if (duel) {
        poller?.stop();
        poller = null;
        return;
    }

    if (!poller) {
        baseline = null;
        poller = createPoller(check, { visible: 5000, hidden: 20000, jitter: 1000 });
        poller.start(0);
    }
}

export function startNotifier() {
    document.addEventListener('turbo:load', sync);

    // Flash lama hilang saat pindah halaman
    document.addEventListener('turbo:visit', () => {
        document.querySelectorAll('#toast-root [data-toast-flash]').forEach((el) => el.remove());
    });
}
