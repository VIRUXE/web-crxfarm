@extends('layouts.app')
@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="mb-1 text-xs font-bold tracking-[0.2em] text-brand uppercase">Store management</p>
            <h1 class="text-3xl font-black tracking-tight text-zinc-950">Photos</h1>
            <p class="mt-1 text-sm text-zinc-500">
                {{ number_format($totals['images']) }} photos across the catalog &middot;
                <span class="{{ $totals['missing'] > 0 ? 'font-semibold text-brand' : '' }}">{{ $totals['missing'] }} listing{{ $totals['missing'] === 1 ? '' : 's' }} with no photo</span>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="inline-flex items-center gap-1.5 rounded-md border px-4 py-2 text-sm font-semibold shadow-sm transition focus:ring-2 focus:ring-brand/20 focus:outline-none {{ $onlyMissing ? 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50' : 'border-brand bg-brand text-white hover:bg-brand-dark' }}"
                href="{{ route('admin.images.index') }}">All photos</a>
            <a class="inline-flex items-center gap-1.5 rounded-md border px-4 py-2 text-sm font-semibold shadow-sm transition focus:ring-2 focus:ring-brand/20 focus:outline-none {{ $onlyMissing ? 'border-brand bg-brand text-white hover:bg-brand-dark' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50' }}"
                href="{{ route('admin.images.index', ['missing' => 1]) }}">
                <x-lucide-image-off class="size-4" /> Missing photos
            </a>
            <a class="inline-flex items-center gap-1.5 rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 focus:ring-2 focus:ring-brand/20 focus:outline-none"
                href="{{ route('admin.listings.index') }}">
                <x-lucide-list class="size-4 text-zinc-500" /> Listings
            </a>
        </div>
    </div>

    <div class="flex flex-col gap-4">
        @forelse($listings as $listing)
            <section class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
                <header class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div class="min-w-0">
                        <a class="truncate text-base font-bold text-zinc-950 hover:text-brand" href="{{ route('admin.listings.edit', $listing) }}">{{ $listing->title }}</a>
                        <p class="text-xs tracking-wide text-zinc-500 uppercase">
                            {{ $listing->type->label() }}
                            @if($listing->chassisLabel()) &middot; {{ $listing->chassisLabel() }} @endif
                            &middot; {{ $listing->images->count() }} photo{{ $listing->images->count() === 1 ? '' : 's' }}
                        </p>
                    </div>
                    <a class="inline-flex items-center gap-1 text-sm font-semibold text-zinc-600 hover:text-brand" href="{{ route('catalog.show', $listing) }}" target="_blank" rel="noopener">
                        View <x-lucide-external-link class="size-3.5" />
                    </a>
                </header>

                @if($listing->images->isEmpty())
                    <div class="flex items-center gap-2 rounded-md bg-zinc-50 px-3 py-6 text-sm text-zinc-500 ring-1 ring-zinc-200">
                        <x-lucide-image-off class="size-5 text-zinc-400" /> No photos on this listing.
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                        @foreach($listing->images as $img)
                            <figure class="group relative overflow-hidden rounded-md ring-1 ring-zinc-200" id="admin-image-{{ $img->id }}">
                                <img class="aspect-square w-full object-cover" src="{{ $img->url }}" alt="{{ $listing->title }} photo {{ $loop->iteration }}" loading="lazy">
                                <span class="pointer-events-none absolute top-1 left-1 rounded bg-black/55 px-1.5 py-0.5 text-[10px] font-bold text-white">#{{ $img->seq + 1 }}</span>
                                <button type="button"
                                    class="absolute top-1 right-1 inline-flex size-7 items-center justify-center rounded-full bg-black/55 text-white opacity-0 transition group-hover:opacity-100 hover:bg-brand focus:opacity-100 focus:ring-2 focus:ring-white/60 focus:outline-none"
                                    aria-label="Delete this photo"
                                    hx-delete="{{ route('admin.images.destroy', $img) }}"
                                    hx-target="#admin-image-{{ $img->id }}"
                                    hx-swap="outerHTML swap:150ms"
                                    hx-confirm="Delete this photo permanently?">
                                    <x-lucide-trash-2 class="size-3.5" />
                                </button>
                            </figure>
                        @endforeach
                    </div>
                @endif
            </section>
        @empty
            <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 shadow-sm">
                {{ $onlyMissing ? 'Every listing has at least one photo.' : 'No listings yet.' }}
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $listings->links() }}
    </div>
@endsection
