const KEY = 'cobit:sound';
const GESTURES = ['pointerdown', 'keydown', 'touchend'];

let ctx = null;
let master = null;

// Nada sintetis: [frekuensi, mulai, durasi, bentuk, frekuensi akhir, volume]
const SOUNDS = {
    turn: [[660, 0, 0.12], [880, 0.11, 0.18]],
    tick: [[1200, 0, 0.03, 'square', null, 0.05]],
    ban: [[300, 0, 0.18, 'triangle', 140]],
    pick: [[520, 0, 0.12, 'triangle', 780], [1040, 0.08, 0.14]],
    opponent: [[440, 0, 0.09, 'sine', null, 0.12]],
    auto: [[330, 0, 0.07], [247, 0.09, 0.1]],
    count: [[523, 0, 0.1]],
    go: [[784, 0, 0.08], [1046, 0.07, 0.3]],
    select: [[700, 0, 0.06, 'sine', null, 0.14]],
    blip: [[880, 0, 0.05, 'sine', null, 0.08]],
    timeout: [[160, 0, 0.25, 'sawtooth', 110, 0.08]],
    done: [[660, 0, 0.1], [990, 0.1, 0.16]],
    win: [[523, 0, 0.1], [659, 0.1, 0.1], [784, 0.2, 0.1], [1046, 0.3, 0.3]],
    lose: [[392, 0, 0.14, 'triangle'], [330, 0.14, 0.14, 'triangle'], [262, 0.28, 0.3, 'triangle']],
    draw: [[523, 0, 0.12], [523, 0.16, 0.2]],
    notify: [[880, 0, 0.14], [660, 0.15, 0.22]],
    match: [[440, 0, 0.08], [554, 0.08, 0.08], [659, 0.16, 0.08], [880, 0.24, 0.26]],
    tierup: [[523, 0, 0.1], [659, 0.09, 0.1], [784, 0.18, 0.1], [1046, 0.27, 0.12], [1318, 0.38, 0.14], [1568, 0.5, 0.6, 'triangle', null, 0.18]],
};

export function isEnabled() {
    try {
        return localStorage.getItem(KEY) !== 'off';
    } catch {
        return true;
    }
}

export function setEnabled(on) {
    try {
        localStorage.setItem(KEY, on ? 'on' : 'off');
    } catch {
        // Penyimpanan diblokir, pakai nilai bawaan
    }

    if (on) unlock();
    else hideHint();
}

function context() {
    if (ctx) return ctx;

    const Audio = window.AudioContext || window.webkitAudioContext;
    if (!Audio) return null;

    ctx = new Audio();
    master = ctx.createGain();
    master.gain.value = 0.6;
    master.connect(ctx.destination);

    return ctx;
}

const running = () => ctx?.state === 'running';

// Browser baru izinkan audio setelah gestur
function unlock() {
    const audio = context();
    if (!audio) return;

    // Hapus chip setelah klik selesai, cegah pergeseran
    const ready = () => {
        if (!running()) return;
        setTimeout(hideHint, 400);
        GESTURES.forEach((type) => document.removeEventListener(type, unlock, true));
    };

    if (audio.state === 'running') ready();
    else audio.resume().then(ready).catch(() => {});
}

function arm() {
    GESTURES.forEach((type) => document.addEventListener(type, unlock, true));
}

function tone([freq, at, dur, type = 'sine', to = null, gain = 0.2]) {
    const start = ctx.currentTime + at;
    const osc = ctx.createOscillator();
    const amp = ctx.createGain();

    osc.type = type;
    osc.frequency.setValueAtTime(freq, start);
    if (to) osc.frequency.exponentialRampToValueAtTime(to, start + dur);

    amp.gain.setValueAtTime(0.0001, start);
    amp.gain.exponentialRampToValueAtTime(gain, start + 0.01);
    amp.gain.exponentialRampToValueAtTime(0.0001, start + dur);

    osc.connect(amp).connect(master);
    osc.start(start);
    osc.stop(start + dur + 0.02);
}

export function play(name) {
    if (!isEnabled() || !SOUNDS[name]) return;

    // Masih terkunci, tawarkan tombol
    if (!running()) {
        showHint();
        return;
    }

    SOUNDS[name].forEach(tone);
}

function showHint() {
    const root = document.getElementById('toast-root');
    if (!root || root.querySelector('[data-sound-hint]')) return;

    const button = document.createElement('button');
    button.type = 'button';
    button.dataset.soundHint = '';
    button.className = 'pointer-events-auto self-end rounded-full bg-navy-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-navy-900/20 transition-colors hover:bg-navy-800 min-h-11';
    button.textContent = 'Nyalakan suara game';
    button.addEventListener('click', () => {
        unlock();
        button.remove();
    });

    // Paling atas, toast di bawahnya tidak bergeser
    root.prepend(button);
}

function hideHint() {
    document.querySelector('[data-sound-hint]')?.remove();
}

export function startSfx() {
    arm();

    // Tab kembali aktif, audio mungkin ditangguhkan
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && ctx && !running()) arm();
    });
}
