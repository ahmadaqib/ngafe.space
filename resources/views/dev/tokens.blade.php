<x-layout.app title="Token ngafe">
    <header class="ngafe-section">
        <p class="ngafe-meta">Styleguide lokal</p>
        <h1>Design system ngafe.space</h1>
        <div class="ngafe-row">
            <a class="ngafe-chip" href="?theme=light" aria-pressed="{{ request('theme', 'light') === 'light' ? 'true' : 'false' }}">Light</a>
            <a class="ngafe-chip" href="?theme=dark" aria-pressed="{{ request('theme') === 'dark' ? 'true' : 'false' }}">Dark</a>
        </div>
    </header>

    <section class="ngafe-section ngafe-stack">
        <h2>Komponen</h2>
        <x-ui.card><h3>Surface dan teks</h3><p class="ngafe-meta">Metadata memakai token text-muted.</p></x-ui.card>
        <div class="ngafe-row">
            <x-ui.button href="#">Primary</x-ui.button>
            <x-ui.button href="#" variant="secondary">Secondary</x-ui.button>
            <x-ui.chip>Chip</x-ui.chip>
            <x-ui.badge variant="open">Buka</x-ui.badge>
            <x-ui.badge variant="closed">Tutup</x-ui.badge>
            <x-ui.badge>Pending</x-ui.badge>
        </div>
        <x-ui.sheet><x-slot:trigger>Buka bottom sheet</x-slot:trigger><h2>Snap 45% / 92%</h2><p>Tarik handle ke atas atau bawah, atau ketuk dua kali.</p></x-ui.sheet>
        <x-ui.skeleton />
    </section>

    <section class="ngafe-section ngafe-stack">
        <h2>Status semantik</h2>
        <p class="ngafe-info">Informasi memakai aksen petrol, bukan CTA.</p>
        <p class="ngafe-warning">Peringatan jam musiman.</p>
        <p class="ngafe-error">Pesan error dengan tindakan pemulihan.</p>
    </section>
</x-layout.app>
