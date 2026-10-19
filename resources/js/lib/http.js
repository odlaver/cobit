// Token CSRF dari meta
export const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// GET JSON tanpa menimpa URL sebelumnya
export async function getJson(url, { signal } = {}) {
    const response = await fetch(url, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        signal,
    });

    if (!response.ok) {
        const error = new Error(`HTTP ${response.status}`);
        error.status = response.status;
        throw error;
    }

    return response.json();
}
