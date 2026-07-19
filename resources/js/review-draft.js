const draftKey = (root) => `ngafe.review.draft.${root.dataset.cafeId}`;
const intentKey = (root) => `ngafe.review.intent.${root.dataset.cafeId}`;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-review-form]').forEach((root) => {
        const saved = JSON.parse(localStorage.getItem(draftKey(root)) || '{}');
        root.querySelectorAll('[data-review-field]').forEach((field) => {
            if (saved[field.dataset.reviewField] && !field.value) {
                field.value = saved[field.dataset.reviewField];
                field.dispatchEvent(new Event('input', { bubbles: true }));
            }
            field.addEventListener('input', () => {
                saved[field.dataset.reviewField] = field.value;
                localStorage.setItem(draftKey(root), JSON.stringify(saved));
            });
        });

        root.querySelector('[data-review-login]')?.addEventListener('click', () => {
            sessionStorage.setItem(intentKey(root), '1');
        });

        if (sessionStorage.getItem(intentKey(root)) === '1') {
            root.scrollIntoView({ behavior: 'smooth' });
            sessionStorage.removeItem(intentKey(root));
        }
    });
});

document.addEventListener('livewire:init', () => {
    Livewire.on('review-submitted', ({ cafeId }) => {
        localStorage.removeItem(`ngafe.review.draft.${cafeId}`);
    });
});
