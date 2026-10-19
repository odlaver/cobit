import * as Turbo from '@hotwired/turbo';

const turboOff = () => document.body?.dataset.turbo === 'false';

// Pindah halaman tanpa membuang dokumen
export function navigate(url, { replace = true } = {}) {
    if (turboOff()) {
        replace ? window.location.replace(url) : window.location.assign(url);
        return;
    }

    Turbo.visit(url, { action: replace ? 'replace' : 'advance' });
}

// Muat ulang halaman saat ini
export function refresh() {
    navigate(window.location.href);
}
