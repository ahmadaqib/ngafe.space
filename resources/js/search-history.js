const key = 'ngafe.search-history';
document.addEventListener('input', (event) => {
    if (!event.target.matches('[data-ngafe-search]') || event.target.value.trim().length < 2) return;
    const term = event.target.value.trim();
    localStorage.setItem(key, JSON.stringify([term, ...JSON.parse(localStorage.getItem(key) || '[]').filter((item) => item !== term)].slice(0, 3)));
});
