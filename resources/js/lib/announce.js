// Bacakan teks lewat live region permanen
export function announce(text) {
    const region = document.getElementById('sr-live');
    if (!region || !text) return;

    region.textContent = '';
    setTimeout(() => {
        region.textContent = text;
    }, 50);
}
