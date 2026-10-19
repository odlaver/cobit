import { Controller } from '@hotwired/stimulus';

// Ringkasan room dan batas jumlah matkul
export default class extends Controller {
    static targets = ['checkbox', 'option', 'group', 'search', 'count', 'summary', 'submit', 'empty'];
    static values = { max: Number };

    connect() {
        this.blocked = this.hasSubmitTarget && this.submitTarget.disabled;
        this.update();
    }

    update() {
        const mode = this.element.querySelector('input[name="mode"]:checked')?.value ?? 'pilih';
        const rounds = Number(this.element.querySelector('input[name="rounds"]:checked')?.value ?? 3);
        const names = this.checkboxTargets.filter((box) => box.checked).map((box) => box.dataset.name);
        const roundText = rounds === 1 ? '1 ronde' : `Best of ${rounds}`;

        // Kunci sisa pilihan saat penuh
        const full = names.length >= this.maxValue;
        this.checkboxTargets.forEach((box) => {
            box.disabled = full && !box.checked;
        });

        if (this.hasCountTarget) this.countTarget.textContent = names.length;
        this.filter();

        let summary = `${this.maxValue} matkul acak · ${roundText}`;

        if (mode === 'pilih') {
            const extra = names.length > 2 ? ` +${names.length - 2}` : '';
            summary = names.length ? `${names.slice(0, 2).join(', ')}${extra} · ${roundText}` : 'Belum ada matkul dipilih.';
        }

        this.summaryTarget.textContent = summary;

        if (this.hasSubmitTarget) this.submitTarget.disabled = this.blocked || (mode === 'pilih' && names.length === 0);
    }

    // Enter di kolom cari tidak mengirim form
    ignore(event) {
        event.preventDefault();
    }

    filter() {
        if (!this.hasSearchTarget) return;

        const query = this.searchTarget.value.trim().toLowerCase();
        let visible = 0;

        this.optionTargets.forEach((option) => {
            const match = !query || option.dataset.search.includes(query);
            option.hidden = !match;
            if (match) visible += 1;
        });

        this.groupTargets.forEach((group) => {
            group.hidden = !group.querySelector('[data-room-target="option"]:not([hidden])');
        });

        if (this.hasEmptyTarget) this.emptyTarget.hidden = visible > 0;
    }
}
