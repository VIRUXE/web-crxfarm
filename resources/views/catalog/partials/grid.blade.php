@if($listings->isEmpty())
    <div class="flex items-center gap-3 rounded-lg border border-sky-200 bg-sky-50 p-5 text-sm text-sky-900">
        <x-lucide-package-search class="size-5 shrink-0 text-sky-600" />
        <span>Nothing matches that search yet. Try a different chassis or keyword.</span>
    </div>
@else
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach($listings as $listing)
            <div class="relative">
            <a class="group flex h-full overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-zinc-200 transition duration-200 hover:-translate-y-1 hover:shadow-xl hover:ring-brand/30" href="{{ route('catalog.show', $listing) }}">
                <article class="flex w-full flex-col">
                <figure class="flex aspect-4/3 items-center justify-center overflow-hidden bg-zinc-100">
                    @if($listing->featuredImage())
                        <img class="h-full w-full object-cover transition duration-300 group-hover:scale-105" src="{{ $listing->featuredImage()->url }}" alt="{{ $listing->title }}">
                    @else
                        <div class="flex flex-col items-center justify-center text-zinc-400">
                            <x-lucide-image-off class="size-7 stroke-[1.5]" />
                            <span class="mt-1 text-[0.7rem] font-medium tracking-wide uppercase">No photo yet</span>
                        </div>
                    @endif
                </figure>
                <div class="flex min-h-48 flex-1 flex-col gap-2 border-t-2 border-brand p-4">
                    <div class="flex flex-wrap items-center gap-1.5">
                        @if($listing->isCar())
                            <span class="inline-flex items-center gap-1 rounded-sm bg-zinc-900 px-2 py-1 text-[0.65rem] font-bold tracking-wider text-white uppercase">
                                <x-lucide-car class="size-3" />
                                Donor car
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-sm bg-red-50 px-2 py-1 text-[0.65rem] font-bold tracking-wider text-brand uppercase ring-1 ring-red-200">
                                <x-lucide-wrench class="size-3" />
                                Part
                            </span>
                        @endif
                        @if($listing->category)
                            <span class="inline-flex rounded-sm bg-zinc-100 px-2 py-1 text-[0.65rem] font-semibold tracking-wider text-zinc-700 uppercase ring-1 ring-zinc-200">{{ $listing->category->label() }}</span>
                        @endif
                        @if($listing->bolt_pattern)
                            <span class="inline-flex rounded-sm bg-amber-50 px-2 py-1 text-[0.65rem] font-bold tracking-wider text-amber-800 uppercase ring-1 ring-amber-200">{{ $listing->bolt_pattern }}</span>
                        @endif
                    </div>
                    <h2 class="text-base leading-6 font-bold text-zinc-950">{{ $listing->title }}</h2>
                    @if($listing->chassisLabel())
                        <span class="text-xs font-bold tracking-[0.14em] text-brand uppercase">{{ $listing->chassisLabel() }}</span>
                    @endif
                    <div class="mt-auto border-t border-zinc-100 pt-3">
                        <span class="text-lg font-black text-zinc-900">{{ $listing->price ?: 'Ask for price' }}</span>
                    </div>
                </div>
                </article>
            </a>
            @if(auth()->user()?->isActive())
                <a class="absolute top-2 right-2 z-10 inline-flex size-8 items-center justify-center rounded-full bg-white/90 text-zinc-700 shadow-sm ring-1 ring-zinc-200 transition hover:bg-zinc-900 hover:text-white focus:ring-2 focus:ring-zinc-400 focus:outline-none"
                    href="{{ route('admin.listings.edit', $listing) }}" aria-label="Edit {{ $listing->title }}" title="Edit listing">
                    <x-lucide-pencil class="size-4" />
                </a>
            @endif
            </div>
        @endforeach
    </div>

    <div class="mt-6">{{ $listings->links() }}</div>
@endif
