function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'ngafe-toast';
    toast.textContent = message;
    toast.setAttribute('role', 'status');
    document.body.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('is-visible'));
    setTimeout(() => {
        toast.classList.remove('is-visible');
        setTimeout(() => toast.remove(), 250);
    }, 2500);
}

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-share-button]');
    if (!button) return;

    const url = button.dataset.shareUrl || window.location.href;
    const title = button.dataset.shareTitle || document.title;

    document.dispatchEvent(new CustomEvent('ngafe:analytics', {
        detail: { event: 'share_tap', cafeId: button.dataset.shareCafeId },
    }));

    if (navigator.share) {
        try {
            await navigator.share({ title, url });
        } catch {
            // Cancelled or unsupported mid-flow — no error surfaced, matches
            // the OS share sheet's own cancel behavior.
        }
        return;
    }

    try {
        await navigator.clipboard.writeText(url);
        showToast('Link kesalin!');
    } catch {
        showToast('Gagal nyalin link. Coba lagi ya.');
    }
});
