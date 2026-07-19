import imageCompression from 'browser-image-compression';

document.addEventListener('change', async (event) => {
    const input = event.target.closest?.('[data-photo-upload]');
    if (!input) return;

    const message = input.parentElement.querySelector('[data-photo-message]');
    const selected = [...input.files];
    if (selected.length > 4) {
        input.value = '';
        message.textContent = 'Maksimal 4 foto dalam satu review.';
        return;
    }
    if (selected.some((file) => !file.type.startsWith('image/') || file.size > 10 * 1024 * 1024)) {
        input.value = '';
        message.textContent = 'Pilih file gambar maksimal 10 MB ya.';
        return;
    }

    message.textContent = 'Lagi mengecilkan foto supaya uploadnya ringan…';
    const compressed = await Promise.all(selected.map((file) => imageCompression(file, {
        maxSizeMB: 0.3,
        maxWidthOrHeight: 1600,
        useWebWorker: true,
        fileType: 'image/webp',
    })));
    const transfer = new DataTransfer();
    compressed.forEach((file, index) => transfer.items.add(new File([file], `${selected[index].name}.webp`, { type: 'image/webp' })));
    input.files = transfer.files;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    message.textContent = 'Foto siap diunggah.';
});
