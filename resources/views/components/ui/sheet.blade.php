@props(['triggerClass' => 'ngafe-button'])
<div x-data="ngafeSheet()" @keydown.escape.window="close()" {{ $attributes }}>
    <button x-ref="trigger" type="button" class="{{ $triggerClass }}" @click="show()">{{ $trigger ?? 'Buka' }}</button>
    <template x-teleport="body">
        <div x-cloak x-show="open">
            <div class="ngafe-sheet-backdrop" x-show="open" x-transition.opacity @click="close()" aria-hidden="true"></div>
            <section x-ref="panel" x-show="open" x-transition :class="expanded ? 'is-full' : 'is-peek'" class="ngafe-sheet" role="dialog" aria-modal="true" tabindex="-1">
                <button type="button" class="ngafe-sheet__handle" aria-label="Geser panel" @dblclick="toggleSnap()" @pointerdown="begin($event)" @pointermove="move($event)" @pointerup="end()" @pointercancel="end()"></button>
                <header class="ngafe-sheet__header"><button type="button" class="ngafe-sheet__close" @click="close()" aria-label="Tutup panel">Tutup</button></header>
                <div class="ngafe-sheet__content">{{ $slot }}</div>
            </section>
        </div>
    </template>
</div>
