@if($listing->exists && $listing->images->isNotEmpty())
  <h2 class="mt-8 mb-4 text-xl font-bold">Photos</h2>
  <p class="-mt-3 mb-4 text-sm text-zinc-500">The starred photo is used for the catalog grid and social/link previews. Defaults to the first photo until you pick one.</p>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" id="image-list">
    @foreach($listing->images as $img)
      @php $isThumbnail = $listing->featuredImage()?->id === $img->id; @endphp
      <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 {{ $isThumbnail ? 'ring-2 ring-brand' : 'ring-zinc-200' }}" id="image-{{ $img->id }}">
        <figure class="relative flex aspect-4/3 items-center justify-center overflow-hidden bg-zinc-100">
          <img class="h-full w-full object-cover" src="{{ $img->url }}" alt="Photo">
          @if($isThumbnail)
            <span class="absolute top-2 left-2 inline-flex items-center gap-1 rounded-full bg-brand px-2.5 py-1 text-xs font-bold text-white shadow-sm">
              <x-lucide-star class="size-3.5 fill-current" />
              Thumbnail
            </span>
          @endif
        </figure>
        <div class="flex flex-wrap gap-2 p-4">
          @unless($isThumbnail)
            <button class="inline-flex items-center justify-center gap-1.5 rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-sm font-semibold text-zinc-700 shadow-sm transition hover:border-brand/40 hover:bg-brand/5 hover:text-brand focus:ring-2 focus:ring-brand/20 focus:outline-none"
              hx-put="{{ route('admin.listings.images.thumbnail', [$listing, $img]) }}"
              hx-target="#image-list" hx-swap="outerHTML swap:200ms">
              <x-lucide-star class="size-4" />
              Set as thumbnail
            </button>
          @endunless
          <button class="inline-flex items-center justify-center gap-1.5 rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-sm font-semibold text-zinc-700 shadow-sm transition hover:border-red-300 hover:bg-red-50 hover:text-brand focus:ring-2 focus:ring-brand/20 focus:outline-none" hx-delete="{{ route('admin.images.destroy', $img) }}"
            hx-target="#image-{{ $img->id }}" hx-swap="outerHTML swap:200ms"
            hx-confirm="Remove this photo?">
            <x-lucide-trash-2 class="size-4 text-red-600" />
            Remove
          </button>
        </div>
      </div>
    @endforeach
  </div>
@endif
