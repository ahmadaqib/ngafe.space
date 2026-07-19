<article>
    <h1>Kontribusimu</h1>
    @forelse($reviews as $review)
        <section class="ngafe-card">
            <h2>{{ $review->cafe->name }}</h2>
            <p>{{ $review->display_alias }} · {{ $review->rating }} bintang</p>
            <p>Status: {{ match($review->status->value) {'published' => 'Tayang', 'pending' => 'Sedang ditinjau', default => 'Diturunkan'} }}</p>
            @if($review->moderation_reason)<p>Catatan: {{ $review->moderation_reason }}</p>@endif
            <a href="{{ route('cafe.show', [$review->cafe->city, $review->cafe->slug]) }}#review-form">Edit reviewmu</a>
        </section>
    @empty
        <p>Belum ada review. Ceritamu nanti muncul di sini.</p>
    @endforelse
</article>
