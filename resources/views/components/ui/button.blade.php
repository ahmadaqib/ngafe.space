@props(['href' => null, 'type' => 'button', 'variant' => 'primary', 'full' => false])
@php($classes = 'ngafe-button'.($variant === 'secondary' ? ' ngafe-button--secondary' : '').($full ? ' ngafe-button--full' : ''))
@if($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
