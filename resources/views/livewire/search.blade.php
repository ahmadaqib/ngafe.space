<div>
    <header><h1>Cari cafe</h1></header>

    <section class="ngafe-section ngafe-stack" aria-labelledby="location-title">
        <h2 id="location-title" class="sr-only">Lokasi pencarian</h2>
        @if($locationState === 'ready')
            <p class="ngafe-info" id="geo-copy">Lokasimu aktif. Cafe terdekat muncul duluan.</p>
        @elseif($locationState === 'denied')
            <p class="ngafe-warning" id="geo-copy">Izin lokasi belum aktif. Tidak apa-apa, pilih area di bawah saja.</p>
        @elseif($locationState === 'unsupported')
            <p class="ngafe-warning" id="geo-copy">Browser ini belum mendukung lokasi. Pilih area di bawah, ya.</p>
        @elseif($locationState === 'error')
            <p class="ngafe-warning" id="geo-copy">Lokasimu belum terbaca. Coba lagi atau pilih area.</p>
        @else
            <p class="ngafe-info" id="geo-copy">Boleh tau posisimu? Biar yang paling dekat muncul duluan.</p>
        @endif
        <div><button class="ngafe-button ngafe-button--secondary" type="button" data-ngafe-geo>Gunakan lokasi</button></div>
        <div class="ngafe-scroll-row" aria-label="Pilih area">
            @foreach($areas as $key => $label)
                <button wire:click="$set('area', {{ $area === $key ? 'null' : "'{$key}'" }})" class="ngafe-chip" aria-pressed="{{ $area === $key ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </div>
    </section>

    <section class="ngafe-section ngafe-stack" aria-labelledby="filter-title">
        <h2 id="filter-title" class="sr-only">Filter pencarian</h2>
        <input class="ngafe-search-input" data-ngafe-search wire:model.live.debounce.250ms="q" placeholder="Cari cafe" aria-label="Cari cafe">
        <div data-search-history-list class="ngafe-row" aria-label="Pencarian terakhir"></div>
        <div class="ngafe-scroll-row">
            @foreach($categories as $category)
                <label class="ngafe-chip"><input type="checkbox" wire:model.live="categorySlugs" value="{{ $category->slug }}">{{ $category->name }}</label>
            @endforeach
        </div>
    </section>

    <p class="ngafe-meta" aria-live="polite">{{ $results->count() }} cafe cocok</p>
    <div class="ngafe-grid">
        @foreach($results as $cafe)
            <a class="ngafe-card ngafe-card--flush" data-ngafe-search-result href="{{ route('cafe.show', ['city' => $cafe->city, 'slug' => $cafe->slug]) }}">
                @if($photo = $cafe->photos->first())
                    <img class="ngafe-photo" src="{{ $photo->url_card }}" width="{{ $photo->width }}" height="{{ $photo->height }}" loading="lazy" alt="Foto {{ $cafe->name }} dari pengunjung">
                @else
                    <div class="ngafe-photo ngafe-photo-placeholder" aria-hidden="true">{{ mb_strtoupper(mb_substr($cafe->name, 0, 1)) }}</div>
                @endif
                <div class="ngafe-card__body">
                    <h2>{{ $cafe->name }}</h2>
                    <p class="ngafe-meta">@if($cafe->rating_count)<span class="ngafe-star">★</span> {{ \App\Support\Format::rating($cafe->rating_avg) }} · {{ $cafe->rating_count }} review @else Belum ada review @endif</p>
                    @if(isset($cafe->distance_km))<p class="ngafe-meta">{{ \App\Support\Format::distance($cafe->distance_km * 1000) }}</p>@endif
                </div>
            </a>
        @endforeach
    </div>

    @if($results->isEmpty())
        <section class="ngafe-empty ngafe-stack">
            <h2>Belum ketemu</h2>
            @if($suggestion)
                <p>Coba lepas “{{ $suggestion['name'] }}” — ada {{ $suggestion['count'] }} cafe lain.</p>
                <div><button class="ngafe-button ngafe-button--secondary" wire:click="removeCategory('{{ $suggestion['slug'] }}')">Lepas {{ $suggestion['name'] }}</button></div>
            @else
                <p>Coba kata lain, pilih area berbeda, atau usulkan cafe yang belum ada.</p>
            @endif
            <a class="ngafe-link" href="#">Usulkan cafe</a>
        </section>
    @endif
</div>
