@extends('layouts.app')
@section('content')
    <a class="mb-5 inline-flex items-center gap-1.5 text-sm font-semibold text-zinc-600 transition hover:text-brand" href="{{ route('catalog.index') }}">
        <x-lucide-arrow-left class="size-4" />
        Back to inventory
    </a>
    <div class="grid gap-7 md:grid-cols-[1.1fr_1fr]">
        <div class="flex flex-col gap-3" data-lightbox-gallery>
            @forelse($listing->images as $image)
                <button type="button"
                    class="group relative block w-full cursor-zoom-in overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-brand/40 focus:outline-none"
                    data-lightbox-open="{{ $loop->index }}"
                    aria-label="View photo {{ $loop->iteration }} of {{ $listing->images->count() }} larger">
                    <img class="w-full object-cover transition duration-200 group-hover:scale-[1.02]" src="{{ $image->url }}" alt="{{ $listing->title }} photo {{ $loop->iteration }}" loading="lazy">
                    <span class="pointer-events-none absolute right-2 bottom-2 inline-flex items-center gap-1 rounded-md bg-black/55 px-2 py-1 text-[11px] font-semibold tracking-wide text-white opacity-0 transition group-hover:opacity-100">
                        <x-lucide-maximize-2 class="size-3" /> Expand
                    </span>
                </button>
            @empty
                <div class="flex aspect-4/3 flex-col items-center justify-center rounded-lg bg-zinc-200 text-xs font-medium tracking-wide text-zinc-500 uppercase ring-1 ring-zinc-300">
                    <x-lucide-image-off class="size-8 stroke-[1.5] text-zinc-400 mb-1" />
                    No photos yet
                </div>
            @endforelse
        </div>
        <aside class="self-start overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-zinc-200 md:sticky md:top-6">
            <div class="h-1.5 bg-brand"></div>
            <div class="flex flex-col gap-4 p-6">
                <div class="flex flex-wrap items-center gap-2">
                    @if($listing->isCar())
                        <span class="inline-flex items-center gap-1.5 rounded-sm bg-zinc-900 px-2.5 py-1 text-xs font-bold tracking-wider text-white uppercase">
                            <x-lucide-car class="size-3.5" />
                            Donor car
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-sm bg-red-50 px-2.5 py-1 text-xs font-bold tracking-wider text-brand uppercase ring-1 ring-red-200">
                            <x-lucide-wrench class="size-3.5" />
                            Part
                        </span>
                    @endif
                    @if($listing->category)
                        <a href="{{ route('catalog.index', ['category' => $listing->category->value]) }}" class="inline-flex rounded-sm bg-zinc-100 px-2.5 py-1 text-xs font-semibold tracking-wider text-zinc-700 uppercase ring-1 ring-zinc-200 hover:bg-zinc-200 transition">
                            {{ $listing->category->label() }}
                        </a>
                    @endif
                </div>
                <h1 class="text-3xl font-black tracking-tight text-zinc-950">{{ $listing->title }}</h1>
                @if($listing->chassisLabel())<p class="text-sm font-bold tracking-[0.14em] text-brand uppercase">{{ $listing->chassisLabel() }}</p>@endif
                <p class="text-3xl font-black text-zinc-900">{{ $listing->price ?: 'Ask for price' }}</p>
                @if($listing->description)
                    <p class="leading-7 text-zinc-700">{{ $listing->description }}</p>
                @endif

                @if($listing->isCar() && count($listing->missingPartsList()) > 0)
                    <hr class="border-zinc-200">
                    <div class="flex flex-col gap-3">
                        <h2 class="text-base font-bold text-zinc-950">Already pulled / missing from this car</h2>
                        <ul class="flex flex-wrap gap-2" role="list" aria-label="Already pulled or missing parts">
                            @foreach($listing->missingPartsList() as $item)
                                <li class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2.5 py-1.5 text-xs font-semibold text-zinc-800 ring-1 ring-zinc-200">
                                    <x-lucide-x class="size-3.5 shrink-0 text-brand" aria-hidden="true" />
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <p class="text-xs leading-5 text-zinc-500">Everything else is likely still on the car. Ask and Jeremiah will confirm.</p>
                    </div>
                @endif

                <a class="mt-2 inline-flex items-center justify-center gap-2 rounded-md bg-brand px-5 py-3 text-sm font-bold tracking-wide text-white uppercase shadow-sm transition hover:bg-brand-dark focus:ring-2 focus:ring-brand/30 focus:outline-none" href="https://www.facebook.com/jeremiah.freeman.116318" target="_blank" rel="noopener">
                    <x-lucide-message-circle class="size-4.5" />
                    Ask Jeremiah about this part
                </a>
                <p class="text-center text-xs text-zinc-500">Message Jeremiah on Facebook for availability and shipping.</p>
            </div>
        </aside>
    </div>

    @if($listing->images->isNotEmpty())
        <div id="lightbox" hidden
            class="fixed inset-0 z-50 hidden items-center justify-center bg-black/90 select-none"
            role="dialog" aria-modal="true" aria-label="{{ $listing->title }} photos">
            <button type="button" data-lightbox-close
                class="absolute top-4 right-4 inline-flex size-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 focus:ring-2 focus:ring-white/50 focus:outline-none"
                aria-label="Close">
                <x-lucide-x class="size-6" />
            </button>

            <button type="button" data-lightbox-prev
                class="absolute left-3 inline-flex size-12 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 focus:ring-2 focus:ring-white/50 focus:outline-none sm:left-6"
                aria-label="Previous photo">
                <x-lucide-chevron-left class="size-7" />
            </button>

            <figure class="flex max-h-[92vh] max-w-[92vw] flex-col items-center gap-3">
                <img data-lightbox-image class="max-h-[86vh] max-w-full rounded-lg object-contain shadow-2xl" src="" alt="{{ $listing->title }}">
                <figcaption class="text-sm font-medium text-white/80">
                    <span data-lightbox-counter></span>
                </figcaption>
            </figure>

            <button type="button" data-lightbox-next
                class="absolute right-3 inline-flex size-12 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 focus:ring-2 focus:ring-white/50 focus:outline-none sm:right-6"
                aria-label="Next photo">
                <x-lucide-chevron-right class="size-7" />
            </button>
        </div>

        <script>
            (function () {
                const urls = @json($listing->images->pluck('url')->values());
                const box = document.getElementById('lightbox');
                if (!box || urls.length === 0) return;

                const img = box.querySelector('[data-lightbox-image]');
                const counter = box.querySelector('[data-lightbox-counter]');
                let index = 0;

                const render = () => {
                    img.src = urls[index];
                    counter.textContent = (index + 1) + ' / ' + urls.length;
                };
                const open = (i) => {
                    index = i;
                    render();
                    box.hidden = false;
                    box.classList.remove('hidden');
                    box.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                };
                const close = () => {
                    box.hidden = true;
                    box.classList.add('hidden');
                    box.classList.remove('flex');
                    document.body.style.overflow = '';
                };
                const step = (d) => { index = (index + d + urls.length) % urls.length; render(); };

                document.querySelectorAll('[data-lightbox-open]').forEach((el) => {
                    el.addEventListener('click', () => open(Number(el.dataset.lightboxOpen)));
                });
                box.querySelector('[data-lightbox-close]').addEventListener('click', close);
                box.querySelector('[data-lightbox-prev]').addEventListener('click', (e) => { e.stopPropagation(); step(-1); });
                box.querySelector('[data-lightbox-next]').addEventListener('click', (e) => { e.stopPropagation(); step(1); });
                // Click the backdrop (but not the image or arrows) to close.
                box.addEventListener('click', (e) => { if (e.target === box) close(); });
                document.addEventListener('keydown', (e) => {
                    if (box.hidden) return;
                    if (e.key === 'Escape') close();
                    else if (e.key === 'ArrowLeft') step(-1);
                    else if (e.key === 'ArrowRight') step(1);
                });
            })();
        </script>
    @endif
@endsection
