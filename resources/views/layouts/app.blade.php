<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@php
    // Pages can override any of these by passing $title, $metaDescription,
    // $ogImage, or $ogType from their controller; everything else falls back
    // to the site-wide defaults below.
    $metaTitle = $title ?? 'CRX Farm - Used Honda Parts & Donor Cars | Rossville, Kansas';
    $metaDescription = $metaDescription ?? 'Browse used Honda parts and complete donor cars from CRX Farm in Rossville, Kansas. 150+ Hondas parted out - CRX, EF, EG, EK, Del Sol, Integra and more. US and international shipping.';
    $usingDefaultOgImage = ! isset($ogImage);
    $ogImage = $ogImage ?? asset('images/social-card.png').'?v='.filemtime(public_path('images/social-card.png'));
    $ogImageWidth = $ogImageWidth ?? ($usingDefaultOgImage ? 1200 : null);
    $ogImageHeight = $ogImageHeight ?? ($usingDefaultOgImage ? 630 : null);
    $ogType = $ogType ?? 'website';
    $canonicalUrl = $canonicalUrl ?? url()->current();
@endphp
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <meta property="og:site_name" content="CRX Farm">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:locale" content="en_US">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ $ogImage }}">
    @if($ogImageWidth && $ogImageHeight)
    <meta property="og:image:width" content="{{ $ogImageWidth }}">
    <meta property="og:image:height" content="{{ $ogImageHeight }}">
    @endif
    @if($usingDefaultOgImage)
    <meta property="og:image:alt" content="CRX Farm - used Honda parts and donor cars, Rossville, Kansas">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}?v={{ filemtime(public_path('images/favicon-32.png')) }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16.png') }}?v={{ filemtime(public_path('images/favicon-16.png')) }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}?v={{ filemtime(public_path('images/apple-touch-icon.png')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body data-base="{{ url('/') }}" class="flex min-h-screen flex-col bg-gray-100 text-gray-900 antialiased" hx-indicator:inherited="#htmx-indicator">
    <div class="h-1 bg-brand"></div>
    <header class="border-b border-zinc-800 bg-zinc-950 px-4 text-white shadow-lg sm:px-6">
        <div class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-4 py-4">
            <a class="group flex items-center gap-3 no-underline" href="{{ route('catalog.index') }}">
                <img src="{{ asset('images/crxfarm-logo-light.png') }}?v={{ filemtime(public_path('images/crxfarm-logo-light.png')) }}" alt="CRX Farm" class="h-10 w-auto">
                <span class="block text-[0.65rem] font-semibold tracking-[0.18em] text-zinc-400 uppercase">Honda parts & donor cars</span>
            </a>
            <div class="text-right text-xs leading-5 text-zinc-300">
                <span class="flex items-center justify-end gap-1.5 font-semibold text-white">
                    <x-lucide-map-pin class="size-3.5 text-brand" />
                    Rossville, Kansas
                </span>
                <span class="flex items-center justify-end gap-1.5 text-zinc-400">
                    <x-lucide-globe class="size-3.5 text-zinc-500" />
                    150+ Hondas parted out &middot; International shipping
                </span>
            </div>
        </div>
    </header>

    <span id="htmx-indicator" class="htmx-indicator fixed top-4 right-4 z-50 size-6 animate-spin rounded-full border-2 border-red-200 border-t-brand" role="status" aria-label="Loading"></span>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 lg:py-10">
        @if(session('status'))
            <div class="mb-6 flex items-center gap-2.5 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">
                <x-lucide-check-circle-2 class="size-4 shrink-0 text-emerald-600" />
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="mt-12 border-t-4 border-brand bg-zinc-950 px-4 text-zinc-300 sm:px-6">
        <div class="mx-auto grid w-full max-w-6xl gap-8 py-10 sm:grid-cols-[1.3fr_1fr_auto] sm:items-start">
            <div>
                <a class="inline-flex items-center no-underline" href="{{ route('catalog.index') }}">
                    <img src="{{ asset('images/crxfarm-logo-light.png') }}?v={{ filemtime(public_path('images/crxfarm-logo-light.png')) }}" alt="CRX Farm" class="h-8 w-auto">
                </a>
                <p class="mt-4 max-w-md text-sm leading-6 text-zinc-400">Used Honda parts and donor cars from Rossville, Kansas. Jeremiah ships parts across the United States and internationally.</p>
            </div>

            <div>
                <h2 class="text-xs font-bold tracking-[0.18em] text-white uppercase">Store information</h2>
                <address class="mt-4 flex flex-col gap-1.5 text-sm leading-6 not-italic text-zinc-400">
                    <span>Owner: Jeremiah Freeman</span>
                    <span class="inline-flex items-center gap-1.5">
                        <x-lucide-map-pin class="size-3.5 text-zinc-500" />
                        Rossville, Kansas
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <x-lucide-car class="size-3.5 text-zinc-500" />
                        Inventory from 150+ Hondas
                    </span>
                </address>
            </div>

            <div class="flex flex-col items-start gap-3 sm:items-end">
                <a class="inline-flex items-center gap-1.5 text-sm font-semibold text-white transition hover:text-brand" href="{{ route('catalog.index') }}">
                    <x-lucide-search class="size-4" />
                    Browse inventory
                </a>
                <a class="inline-flex items-center justify-center gap-2 rounded-md bg-brand px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark focus:ring-2 focus:ring-red-400/50 focus:outline-none" href="https://m.me/jeremiah.freeman.116318" target="_blank" rel="noopener">
                    <x-lucide-message-circle class="size-4" />
                    Message Jeremiah
                </a>
                <div class="flex items-center gap-3">
                    <a class="text-zinc-400 transition hover:text-brand" href="https://www.instagram.com/crx.farm/" target="_blank" rel="noopener" aria-label="CRX Farm on Instagram">
                        <x-lucide-instagram class="size-5" />
                    </a>
                    <a class="text-zinc-400 transition hover:text-brand" href="https://www.facebook.com/profile.php?id=100083512851607" target="_blank" rel="noopener" aria-label="CRX Farm on Facebook">
                        <x-lucide-facebook class="size-5" />
                    </a>
                </div>
            </div>
        </div>

        <div class="mx-auto flex w-full max-w-6xl flex-wrap justify-between gap-2 border-t border-zinc-800 py-5 text-xs text-zinc-500">
            <p>&copy; {{ now()->year }} CRX Farm. All rights reserved.</p>
            <p>Independent used parts store.</p>
        </div>
    </footer>

    @yield('scripts')
</body>
</html>
