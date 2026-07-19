@php($theme = in_array(request()->query('theme'), ['light', 'dark'], true) ? request()->query('theme') : null)
<!doctype html>
<html lang="id" @if($theme) data-theme="{{ $theme }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ?? 'ngafe.space' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <main class="ngafe-shell">{{ $slot }}</main>
    <nav class="ngafe-nav" aria-label="Navigasi utama">
        <a href="/" @if(request()->is('/')) aria-current="page" @endif>Jelajah</a>
        <a href="{{ route('search') }}" @if(request()->routeIs('search')) aria-current="page" @endif>Cari</a>
        <a href="{{ auth()->check() ? route('my-contributions') : '#review-form' }}" @if(request()->routeIs('my-contributions')) aria-current="page" @endif>Kamu</a>
    </nav>
    @livewireScripts
</body>
</html>
