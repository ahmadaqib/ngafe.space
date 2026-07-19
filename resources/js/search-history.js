const key = 'ngafe.search-history';

const read = () => {
    try { return JSON.parse(localStorage.getItem(key) || '[]').filter((item) => typeof item === 'string').slice(0, 3); }
    catch { return []; }
};

const remember = (term) => {
    term = term.trim();
    if (term.length < 2) return;
    localStorage.setItem(key, JSON.stringify([term, ...read().filter((item) => item.toLowerCase() !== term.toLowerCase())].slice(0, 3)));
    render();
};

const render = () => {
    document.querySelectorAll('[data-search-history-list]').forEach((list) => {
        list.replaceChildren();
        read().forEach((term) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ngafe-chip';
            button.textContent = term;
            button.addEventListener('click', () => {
                const input = document.querySelector('[data-ngafe-search]');
                if (!input) return;
                input.value = term;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
            list.append(button);
        });
    });
};

document.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && event.target.matches('[data-ngafe-search]')) remember(event.target.value);
});
document.addEventListener('click', (event) => {
    if (event.target.closest('[data-ngafe-search-result]')) remember(document.querySelector('[data-ngafe-search]')?.value || '');
});
document.addEventListener('DOMContentLoaded', render);
document.addEventListener('livewire:navigated', render);
