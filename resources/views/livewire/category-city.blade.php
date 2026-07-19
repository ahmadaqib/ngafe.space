<div class="ngafe-stack">
    <header class="ngafe-stack">
        <p class="ngafe-meta">ngafe.space · {{ ucfirst($category->name) }}</p>
        <h1>{{ $category->name }} di {{ ucfirst($city) }}</h1>
        <p>Direktori cafe {{ $category->name }} — review jujur dari pengunjung asli, bukan endorse.</p>
    </header>

    <section class="ngafe-grid" aria-label="Daftar cafe {{ $category->name }}">
        @forelse($cafes as $cafe)
            <a class="ngafe-card ngafe-card--flush" href="{{ route('cafe.show', ['city' => $cafe->city, 'slug' => $cafe->slug]) }}">
                @if($photo = $cafe->photos->first())
                    <img class="ngafe-photo" src="{{ $photo->url_card }}" srcset="{{ $photo->url_card }} 400w, {{ $photo->url_full }} 1600w" sizes="(max-width: 520px) 100vw, 360px" width="{{ $photo->width }}" height="{{ $photo->height }}" loading="lazy" alt="Foto {{ $cafe->name }} dari pengunjung">
                @else
                    <div class="ngafe-photo ngafe-photo-placeholder" aria-hidden="true">{{ mb_strtoupper(mb_substr($cafe->name, 0, 1)) }}</div>
                @endif
                <div class="ngafe-card__body">
                    <h2>{{ $cafe->name }}</h2>
                    <p class="ngafe-meta">@if($cafe->rating_count)<span class="ngafe-star">★</span> {{ \App\Support\Format::rating($cafe->rating_avg) }} · {{ $cafe->rating_count }} review @else Belum ada review @endif</p>
                </div>
            </a>
        @empty
            <p>Belum ada cafe {{ $category->name }} yang terdaftar di sini. <a class="ngafe-link" href="{{ route('search') }}">Cari area lain?</a></p>
        @endforelse
    </section>
</div>
