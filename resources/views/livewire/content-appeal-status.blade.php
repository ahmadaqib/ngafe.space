<article>
    <h1>Status Keberatan Konten</h1>
    <p>Status: {{ str($appeal->status)->replace('_', ' ')->title() }}</p>
    @if($appeal->decision)<div class="ngafe-card"><h2>Keputusan tertulis</h2><p>{{ $appeal->decision }}</p></div>@endif
    @if(!$submitted && $appeal->status !== 'submitted' && $appeal->appeal_count === 0)
        <form wire:submit="appealOnce" class="ngafe-card">
            <h2>Ajukan satu kali banding</h2>
            <label>Email yang dipakai sebelumnya<input wire:model="email" type="email" required></label>
            <label>Alasan dan bukti tambahan<textarea wire:model="reason" minlength="30" required></textarea></label>
            <button type="submit">Kirim banding</button>
        </form>
    @elseif($submitted)
        <p>Banding sudah diterima dan konten kembali masuk antrian peninjauan.</p>
    @endif
</article>
