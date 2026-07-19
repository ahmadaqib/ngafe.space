<section id="review-form" data-review-form data-cafe-id="{{ $cafe->id }}" class="ngafe-card">
    @if($submitted)
        @php($successCopies = ['Reviewmu tayang! 🎉 Kamu barusan bantu orang lain nggak salah pilih tempat.', 'Mantap, ceritamu sudah ikut bikin rekomendasi makin jujur.', 'Satu review jujur selesai. Makassar berterima kasih!'])
        <h2>{{ $successCopies[crc32((string) $submittedReviewId) % count($successCopies)] }}</h2>
        <p>Reviewmu langsung terlihat di kontribusi dan halaman cafe sesuai status moderasinya.</p>
        @if($submittedReview)
            <article class="ngafe-card" aria-label="Review yang baru dikirim">
                <p><strong>{{ $submittedReview->display_alias }}</strong> · {{ $submittedReview->rating }}/5</p>
                <p>{{ $submittedReview->body }}</p>
                @if($submittedReview->status->value === 'pending')
                    <x-ui.badge>sedang ditinjau</x-ui.badge>
                @endif
            </article>
        @endif
        <a class="ngafe-button" href="/cari">Mau review cafe lain yang pernah kamu datangi?</a>
    @elseif(! auth()->check())
        <h2>Tulis review anonim</h2>
        <p>Nama aslimu tidak akan tampil. Draft disimpan di perangkat ini.</p>
        <x-ui.sheet>
            <x-slot:trigger>Tulis review</x-slot:trigger>
            <h3>Login sebentar biar reviewmu tersimpan</h3>
            <p>Nama aslimu nggak akan tampil — review tetap anonim.</p>
            <a data-review-login class="ngafe-button" href="{{ route('auth.google.redirect', ['intended' => request()->fullUrl().'#review-form', 'intent' => 'review:'.$cafe->id]) }}">Lanjut dengan Google</a>
            <p><small>Batal? Tutup saja sheet ini. Draftmu tetap aman.</small></p>
        </x-ui.sheet>
    @else
        <header>
            <p>Langkah {{ $step }} dari 3 · {{ $step === 1 ? 'Rating' : ($step === 2 ? 'Cerita' : 'Foto opsional') }}</p>
            <progress max="3" value="{{ $step }}" aria-label="Langkah {{ $step }} dari 3"></progress>
            <h2>{{ $existingReview ? 'Edit reviewmu' : 'Ceritakan versimu' }}</h2>
        </header>

        <form wire:submit="submit" data-review-draft>
            <input class="ngafe-honeypot" wire:model="website" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">

            @if($step === 1)
                <fieldset>
                    <legend>Kasih rating</legend>
                    <div class="ngafe-rating-row">
                        @foreach(range(1, 5) as $star)
                            <label class="ngafe-rating-option">
                                <input type="radio" wire:model="rating" value="{{ $star }}" data-review-field="rating">
                                <span aria-hidden="true">★</span><span class="sr-only">{{ $star }} bintang</span>
                            </label>
                        @endforeach
                    </div>
                    @error('rating') <p role="alert">{{ $message }}</p> @enderror
                </fieldset>
                <fieldset>
                    <legend>Yang paling terasa apa? <small>(opsional)</small></legend>
                    @foreach($categories as $category)
                        <label class="ngafe-chip"><input type="checkbox" wire:model="tagIds" value="{{ $category->id }}"> {{ $category->name }}</label>
                    @endforeach
                </fieldset>
            @elseif($step === 2)
                <label for="review-body">Ceritamu</label>
                <textarea id="review-body" wire:model="body" data-review-field="body" minlength="30" maxlength="5000" placeholder="Wifinya gimana? Betah berapa jam? Habis berapa?" rows="7"></textarea>
                <p><small>Minimal 30 karakter. Hindari menulis nama lengkap atau identitas orang lain.</small></p>
                @error('body') <p role="alert">{{ $message }}</p> @enderror
            @else
                <label for="review-photos">Foto <small>(opsional, maks 4)</small></label>
                <input id="review-photos" type="file" wire:model="photos" data-photo-upload multiple accept="image/jpeg,image/png,image/webp">
                <p data-photo-message><small>Maks 10 MB per foto. Kami kompres ke WebP sebelum upload.</small></p>
                <p><small>Cek dulu: nggak ada wajah atau namamu kefoto kan?</small></p>
                @foreach($photos as $index => $photo)
                    <img src="{{ $photo->temporaryUrl() }}" alt="Preview foto {{ $index + 1 }}" width="120">
                @endforeach
                @error('photos.*') <p role="alert">{{ $message }}</p> @enderror
            @endif

            <div class="ngafe-form-actions">
                @if($step > 1)<button type="button" wire:click="previousStep">Kembali</button>@endif
                @if($step < 3)
                    <button type="button" wire:click="nextStep">Lanjut</button>
                @else
                    <button type="submit">{{ $existingReview ? 'Simpan perubahan' : 'Tayangkan review' }}</button>
                @endif
            </div>
        </form>
    @endif
</section>
