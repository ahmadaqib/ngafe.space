<article>
    <h1>Keberatan atas Konten</h1>
    <p>Saluran ini dapat dipakai tanpa akun. Kami meninjau dalam paling lambat 3×24 jam, memberi keputusan tertulis, dan menyediakan satu kali banding.</p>
    @if($submitted)
        <div class="ngafe-card"><h2>Keberatan sudah diterima</h2><p>Review sementara disembunyikan selama peninjauan. Keputusan akan dikirim ke email yang kamu tulis.</p></div>
    @else
        <form wire:submit="submit" class="ngafe-card">
            <p>Konten: review {{ $review->display_alias }} di {{ $review->cafe->name }}</p>
            <label>Nama pelapor<input wire:model="name" required maxlength="120"></label>@error('name')<p>{{ $message }}</p>@enderror
            <label>Email<input wire:model="email" type="email" required></label>@error('email')<p>{{ $message }}</p>@enderror
            <label>Alasan keberatan<textarea wire:model="reason" required minlength="30" rows="7"></textarea></label>@error('reason')<p>{{ $message }}</p>@enderror
            <button type="submit">Kirim keberatan</button>
        </form>
    @endif
</article>
