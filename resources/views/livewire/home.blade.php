<div>
    <header class="ngafe-stack">
        <p class="ngafe-meta">ngafe.space · Makassar</p>
        <h1>Cari space-mu buat ngafe</h1>
        <a class="ngafe-card" href="{{ route('search') }}">Cari cafe atau area</a>
    </header>

    <section class="ngafe-section" aria-labelledby="category-title">
        <h2 id="category-title" class="sr-only">Pilih suasana</h2>
        <div class="ngafe-scroll-row">
            @foreach($categories->take(6) as $category)
                <a class="ngafe-chip" href="{{ route('search', ['categories' => [$category->slug]]) }}">{{ $category->name }}</a>
            @endforeach
            @if($categories->count() > 6)
                <x-ui.sheet trigger-class="ngafe-chip">
                    <x-slot:trigger>Lainnya</x-slot:trigger>
                    <h2>Pilih suasana</h2>
                    <div class="ngafe-row">
                        @foreach($categories->skip(6) as $category)
                            <a class="ngafe-chip" href="{{ route('search', ['categories' => [$category->slug]]) }}">{{ $category->name }}</a>
                        @endforeach
                    </div>
                </x-ui.sheet>
            @endif
        </div>
    </section>

    <section class="ngafe-section" aria-labelledby="trending-title">
        <h2 id="trending-title">Lagi rame dibahas</h2>
        <div class="ngafe-grid">
            @foreach($cafes as $cafe)
                <a class="ngafe-card ngafe-card--flush" href="{{ route('cafe.show', ['city' => $cafe->city, 'slug' => $cafe->slug]) }}">
                    @if($photo = $cafe->photos->first())
                        <img class="ngafe-photo" src="{{ $photo->url_card }}" srcset="{{ $photo->url_card }} 400w, {{ $photo->url_full }} 1600w" sizes="(max-width: 520px) 100vw, 360px" width="{{ $photo->width }}" height="{{ $photo->height }}" loading="lazy" alt="Foto {{ $cafe->name }} dari pengunjung">
                    @else
                        <div class="ngafe-photo ngafe-photo-placeholder" aria-hidden="true">{{ mb_strtoupper(mb_substr($cafe->name, 0, 1)) }}</div>
                    @endif
                    <div class="ngafe-card__body">
                        <h3>{{ $cafe->name }}</h3>
                        <p class="ngafe-meta">@if($cafe->rating_count)<span class="ngafe-star">★</span> {{ \App\Support\Format::rating($cafe->rating_avg) }} · {{ $cafe->rating_count }} review @else Belum ada review @endif</p>
                        <p class="ngafe-meta">{{ $cafe->categories->take(2)->pluck('name')->join(' · ') }}</p>
                        @if($excerpt = \App\Support\Format::reviewExcerpt($cafe->reviews->first()?->body))
                            <p class="ngafe-review-excerpt">“{{ $excerpt }}”</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </section>
</div>
