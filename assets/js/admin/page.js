import Sortable from 'sortablejs';

function initPagesSortable() {
    const container = document.getElementById('pages-sortable');

    if (!container) {
        return;
    }

    const url = container.dataset.reorderUrl;

    new Sortable(container, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'opacity-50',
        onMove: (evt) => !evt.related.classList.contains('no-drag'),
        onUpdate: async () => {
            const ids = Array.from(container.querySelectorAll('[data-id]'))
                .map(el => parseInt(el.dataset.id));

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids }),
                });

                if (!response.ok) {
                    alert('Erreur lors de la réorganisation des pages.');
                }
            } catch {
                alert('Erreur réseau, veuillez réessayer.');
            }
        },
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initPagesSortable();
});
