<x-layout.app :title="$cafe->name.' · ngafe.space'">
    <article>
        <section aria-label="Galeri foto {{ $cafe->name }}">
            @if($cafe->photos->isNotEmpty())
                <div class="ngafe-gallery">
                    @foreach($cafe->photos as $photo)
                        <figure class="ngafe-gallery-slide">
                            <img class="ngafe-photo" src="{{ $photo->url_full }}" srcset="{{ $photo->url_card }} 400w, {{ $photo->url_full }} 1600w" sizes="(max-width: 760px) 100vw, 760px" width="{{ $photo->width }}" height="{{ $photo->height }}" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif alt="Foto {{ $cafe->name }} dari pengunjung">
                        </figure>
                    @endforeach
                </div>
                @if($cafe->photos->count() > 1)
                    <div class="ngafe-gallery-dots" aria-hidden="true">@foreach($cafe->photos as $photo)<span class="ngafe-dot"></span>@endforeach</div>
                @endif
            @else
                <div class="ngafe-photo ngafe-photo-placeholder" aria-hidden="true">{{ mb_strtoupper(mb_substr($cafe->name, 0, 1)) }}</div>
            @endif
        </section>

        <header class="ngafe-section ngafe-stack">
            <h1>{{ $cafe->name }}</h1>
            <p class="ngafe-meta">
                @if($cafe->rating_count)
                    <span class="ngafe-star">★</span> {{ \App\Support\Format::rating($cafe->rating_avg) }} · {{ $cafe->rating_count }} review
                @else
                    Belum ada review — jadi yang pertama?
                @endif
                @if($price = \App\Support\Format::priceRange($cafe->price_range)) · {{ $price }} @endif
            </p>
            <p><x-ui.badge :variant="$opening->isOpen ? 'open' : 'closed'">{{ $opening->label }}</x-ui.badge></p>
            @if($opening->activeOverride)<p class="ngafe-warning">{{ $opening->activeOverride }}</p>@endif
            <div class="ngafe-row">@foreach($cafe->categories as $category)<span class="ngafe-chip">{{ $category->name }}</span>@endforeach</div>
            <div><a class="ngafe-button ngafe-button--secondary" href="https://www.google.com/maps/search/?api=1&amp;query={{ $cafe->lat }},{{ $cafe->lng }}" rel="external">Arah</a></div>
        </header>

        <section id="review-list" class="ngafe-section ngafe-stack" data-review-list>
            <h2>Review</h2>
            @forelse($cafe->reviews as $review)
                <article class="ngafe-card" id="review-{{ $review->id }}">
                    <div class="ngafe-row"><strong>{{ $review->display_alias }}</strong><span class="ngafe-star" aria-label="{{ $review->rating }} dari 5">{{ str_repeat('★', $review->rating) }}</span>@if($review->status->value === 'pending')<x-ui.badge variant="pending">sedang ditinjau</x-ui.badge>@endif</div>
                    <p>{{ $review->body }}</p>
                    @foreach($review->photos->where('status', 'published') as $photo)
                        <figure class="ngafe-review-photo">
                            <img class="ngafe-photo" src="{{ $photo->url_card }}" srcset="{{ $photo->url_card }} 400w, {{ $photo->url_full }} 1600w" sizes="(max-width: 600px) 100vw, 600px" width="{{ $photo->width }}" height="{{ $photo->height }}" loading="lazy" alt="Foto {{ $cafe->name }} dari pengunjung">
                            @auth
                                <form method="POST" action="{{ route('photos.report', $photo) }}">@csrf<input type="hidden" name="reason" value="membuka_identitas"><button class="ngafe-link" type="submit">Laporkan foto</button></form>
                            @endauth
                        </figure>
                    @endforeach
                    <p class="ngafe-meta">{{ $review->created_at->locale('id')->diffForHumans() }}@if($review->is_edited) · diedit @endif</p>
                    @auth
                        <details><summary>Laporkan</summary><form class="ngafe-stack" method="POST" action="{{ route('reviews.report', $review) }}">@csrf<label>Alasan<select name="reason" required><option value="spam">Spam</option><option value="kasar">Kasar</option><option value="bukan_tentang_cafe">Bukan tentang cafe ini</option><option value="info_salah">Info salah</option><option value="membuka_identitas">Membuka identitas seseorang</option></select></label><textarea name="note" maxlength="1000" placeholder="Catatan tambahan (opsional)"></textarea><button class="ngafe-button" type="submit">Kirim laporan</button></form></details>
                    @endauth
                    <a class="ngafe-link" href="{{ route('content-appeal', ['review' => $review->id]) }}">Keberatan atas konten ini</a>
                </article>
            @empty
                <p>Belum ada review.</p>
            @endforelse
        </section>

        <section class="ngafe-section ngafe-stack">
            <h2>Pernah ke sini? Ceritakan versimu</h2>
            <livewire:review-form :cafe="$cafe" />
        </section>
        <a data-sticky-review-cta class="ngafe-button ngafe-sticky-cta" href="#review-form" hidden>Tulis review</a>
    </article>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const list = document.querySelector('[data-review-list]');
            const cta = document.querySelector('[data-sticky-review-cta]');
            if (list && cta) new IntersectionObserver(([entry]) => { cta.hidden = entry.isIntersecting; }).observe(list);
        });
    </script>
</x-layout.app>
