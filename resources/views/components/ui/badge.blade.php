@props(['variant' => 'pending'])
<span {{ $attributes->class(['ngafe-badge', 'ngafe-badge--'.$variant]) }}>{{ $slot }}</span>
