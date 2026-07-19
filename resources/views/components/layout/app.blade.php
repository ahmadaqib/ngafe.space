@props(['title' => null, 'description' => null, 'canonical' => null, 'image' => null, 'jsonLd' => null])
@php($theme = in_array(request()->query('theme'), ['light', 'dark'], true) ? request()->query('theme') : null)
@php($pageTitle = $title ?? 'ngafe.space')
<!doctype html>
<html lang="id" @if($theme) data-theme="{{ $theme }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $pageTitle }}</title>
    @if($description)
        <meta name="description" content="{{ $description }}">
    @endif
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <meta property="og:site_name" content="ngafe.space">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    @if($description)
        <meta property="og:description" content="{{ $description }}">
    @endif
    @if($image)
        <meta property="og:image" content="{{ $image }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#C4451C">
    @if($jsonLd)
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
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
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
        }
    </script>
</body>
</html>
