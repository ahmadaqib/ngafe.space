document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-ngafe-geo]');
    if (!button) return;

    const copy = document.querySelector('#geo-copy');
    button.disabled = true;
    button.textContent = 'Mencari lokasi…';

    const finish = () => {
        button.disabled = false;
        button.textContent = 'Gunakan lokasi';
    };

    if (!navigator.geolocation) {
        copy && (copy.textContent = 'Browser ini belum mendukung lokasi. Pilih area di bawah, ya.');
        window.Livewire?.dispatch('geo-failed', { reason: 'unsupported' });
        finish();
        return;
    }

    navigator.geolocation.getCurrentPosition(
        ({ coords }) => {
            window.Livewire?.dispatch('geo-ready', { lat: coords.latitude, lng: coords.longitude });
            finish();
        },
        (error) => {
            const reason = error.code === error.PERMISSION_DENIED ? 'denied' : 'error';
            copy && (copy.textContent = reason === 'denied' ? 'Izin lokasi belum aktif. Tidak apa-apa, pilih area di bawah saja.' : 'Lokasimu belum terbaca. Coba lagi atau pilih area.');
            window.Livewire?.dispatch('geo-failed', { reason });
            finish();
        },
        { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 },
    );
});
