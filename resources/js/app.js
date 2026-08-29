import htmx from 'htmx.org';
import { createIcons, icons, X } from 'lucide';

window.htmx = htmx;
window.lucide = { createIcons, icons };

document.addEventListener('htmx:config:request', (event) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (csrfToken) {
        event.detail.ctx.request.headers['X-CSRF-TOKEN'] = csrfToken;
    }
});

window.addMissingPartRow = function (value = '') {
    const container = document.getElementById('missing-parts-container');
    if (!container) return;

    const row = document.createElement('div');
    row.className = 'flex items-center gap-2 missing-part-row';
    row.innerHTML = `
        <input
            type="text"
            name="missing_parts[]"
            list="part-suggestions"
            autocomplete="off"
            value="${value.replace(/"/g, '&quot;')}"
            placeholder="e.g. Hood, Driver seat, ECU..."
            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/15"
        >
        <button
            type="button"
            class="inline-flex shrink-0 items-center justify-center rounded-md border border-zinc-300 bg-white p-2 text-zinc-500 shadow-xs hover:border-red-300 hover:bg-red-50 hover:text-brand transition cursor-pointer"
            onclick="this.closest('.missing-part-row').remove()"
            aria-label="Remove item"
            title="Remove item"
        >
            <i data-lucide="x" class="size-4"></i>
        </button>
    `;
    container.appendChild(row);
    createIcons({
        root: row,
        icons: { X },
    });
    const input = row.querySelector('input');
    if (input) input.focus();
};

document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && e.target && e.target.matches && e.target.matches('input[name="missing_parts[]"]')) {
        e.preventDefault();
        window.addMissingPartRow();
    }
});

window.updateListingTypeFields = function (form) {
    if (!form) return;

    const checked = form.querySelector('input[name="type"]:checked');
    const type = checked ? checked.value : null;
    const categorySection = form.querySelector('#category-section');
    const boltPatternSection = form.querySelector('#bolt-pattern-section');
    const missingPartsSection = form.querySelector('#missing-parts-section');
    const chassisCarSection = form.querySelector('#chassis-car-section');
    const chassisPartSection = form.querySelector('#chassis-part-section');

    if (categorySection) categorySection.classList.toggle('hidden', type === 'car');
    if (boltPatternSection) boltPatternSection.classList.toggle('hidden', type === 'car');
    if (missingPartsSection) missingPartsSection.classList.toggle('hidden', type === 'part');
    if (chassisCarSection) chassisCarSection.classList.toggle('hidden', type === 'part');
    if (chassisPartSection) chassisPartSection.classList.toggle('hidden', type === 'car');
};

function initListingTypeFields() {
    document.querySelectorAll('form').forEach((form) => {
        if (form.querySelector('input[name="type"]')) {
            window.updateListingTypeFields(form);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initListingTypeFields();
    createIcons({ icons });
});

document.body.addEventListener('htmx:afterSwap', (event) => {
    initListingTypeFields();
    createIcons({ icons, root: event.detail?.target || document });
});


