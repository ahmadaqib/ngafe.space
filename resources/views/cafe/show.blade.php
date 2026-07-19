<x-layout.app :title="$cafe->name.' · ngafe.space'">
    <article>
        <div class="ngafe-photo">
            @if($cafe->photos->first())
                <img class="ngafe-photo" src="{{ $cafe->photos->first()->url_full }}" alt="Foto {{ $cafe->name }} dari pengunjung">
            @endif
        </div>
        <h1>{{ $cafe->name }}</h1>
        <p>{{ $cafe->rating_count > 0 ? str_replace('.', ',', $cafe->rating_avg).' · '.$cafe->rating_count.' review' : 'Belum ada review — jadi yang pertama?' }} · {{ $opening->label }}</p>
        @if($opening->activeOverride)<p>{{ $opening->activeOverride }}</p>@endif
        <p>@foreach($cafe->categories as $category)<span class="ngafe-chip">{{ $category->name }}</span> @endforeach</p>
        <a class="ngafe-button" href="geo:{{ $cafe->lat }},{{ $cafe->lng }}?q={{ $cafe->lat }},{{ $cafe->lng }}">Arah</a>

        <section id="review-list" data-review-list>
            <h2>Review</h2>
            @forelse($cafe->reviews as $review)
                <section class="ngafe-card" id="review-{{ $review->id }}">
                    <b>{{ $review->display_alias }}</b>
                    @if($review->status->value === 'pending')<small>sedang ditinjau</small>@endif
                    <p>{{ $review->body }}</p>
                    @foreach($review->photos->where('status', 'published') as $photo)
                        <figure>
                            <img src="{{ $photo->url_card }}" srcset="{{ $photo->url_card }} 400w, {{ $photo->url_full }} 1600w" sizes="(max-width: 600px) 100vw, 600px" width="{{ $photo->width }}" height="{{ $photo->height }}" loading="lazy" alt="Foto {{ $cafe->name }} dari pengunjung">
                            @auth
                                <form method="POST" action="{{ route('photos.report', $photo) }}">
                                    @csrf
                                    <input type="hidden" name="reason" value="membuka_identitas">
                                    <button type="submit">Laporkan foto</button>
                                </form>
                            @endauth
                        </figure>
                    @endforeach
                    <small>{{ $review->created_at->diffForHumans() }}</small>
                    @auth
                        <details>
                            <summary>Laporkan</summary>
                            <form method="POST" action="{{ route('reviews.report', $review) }}">
                                @csrf
                                <label>Alasan
                                    <select name="reason" required>
                                        <option value="spam">Spam</option>
                                        <option value="kasar">Kasar</option>
                                        <option value="bukan_tentang_cafe">Bukan tentang cafe ini</option>
                                        <option value="info_salah">Info salah</option>
                                        <option value="membuka_identitas">Membuka identitas seseorang</option>
                                    </select>
                                </label>
                                <textarea name="note" maxlength="1000" placeholder="Catatan tambahan (opsional)"></textarea>
                                <button type="submit">Kirim laporan</button>
                            </form>
                        </details>
                    @endauth
                    <a href="{{ route('content-appeal', ['review' => $review->id]) }}">Keberatan atas konten ini</a>
                </section>
            @empty
                <p>Belum ada review.</p>
            @endforelse
        </section>

        <livewire:review-form :cafe="$cafe" />
        <a data-sticky-review-cta class="ngafe-button" href="#review-form" style="display:none;position:fixed;bottom:72px;right:16px">Tulis review</a>
    </article>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const list = document.querySelector('[data-review-list]');
            const cta = document.querySelector('[data-sticky-review-cta]');
            if (list && cta) new IntersectionObserver(([entry]) => cta.style.display = entry.isIntersecting ? 'none' : 'inline-flex').observe(list);
        });
    </script>
</x-layout.app>
