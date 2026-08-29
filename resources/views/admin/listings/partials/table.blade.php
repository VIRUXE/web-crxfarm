@if($listings->isEmpty())
    <div class="flex items-center gap-3 rounded-lg border border-sky-200 bg-sky-50 p-5 text-sm text-sky-900">
        <x-lucide-package-search class="size-5 shrink-0 text-sky-600" />
        <span>{{ request()->filled('q') ? 'Nothing matches that search yet. Try a different keyword.' : 'No listings yet.' }}</span>
    </div>
@else
    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
            <thead class="bg-zinc-50 text-xs tracking-wider text-zinc-500 uppercase">
                <tr>
                    <th class="px-4 py-3 font-bold">Title</th>
                    <th class="px-4 py-3 font-bold">Type</th>
                    <th class="px-4 py-3 font-bold">Chassis</th>
                    <th class="px-4 py-3 font-bold">Category</th>
                    <th class="px-4 py-3 font-bold">Price</th>
                    <th class="px-4 py-3 font-bold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach($listings as $listing)
                    <tr class="transition hover:bg-zinc-50">
                        <td class="px-4 py-3 font-semibold text-zinc-950">
                            <a class="hover:text-brand hover:underline" href="{{ route('admin.listings.edit', $listing) }}">{{ $listing->title }}</a>
                        </td>
                        <td class="px-4 py-3 text-zinc-600">
                            <span class="inline-flex items-center gap-1">
                                @if($listing->isCar())
                                    <x-lucide-car class="size-3.5 text-zinc-500" />
                                @else
                                    <x-lucide-wrench class="size-3.5 text-zinc-500" />
                                @endif
                                {{ $listing->type?->label() ?? $listing->type }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-zinc-600">{{ $listing->chassisLabel() }}</td>
                        <td class="px-4 py-3 text-zinc-600">
                            {{ $listing->category?->label() ?? '-' }}
                            @if($listing->bolt_pattern)
                                <span class="ml-1.5 inline-flex rounded-sm bg-amber-50 px-1.5 py-0.5 text-[0.65rem] font-bold tracking-wider text-amber-800 ring-1 ring-amber-200 uppercase">{{ $listing->bolt_pattern }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-semibold text-zinc-800">{{ $listing->price ?: 'ask' }}</td>
                        <td class="px-4 py-3">
                            @switch($listing->status)
                                @case('available')
                                    <span class="inline-flex items-center gap-1 rounded-full border border-emerald-300 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                        <x-lucide-check-circle-2 class="size-3 text-emerald-600" />
                                        Available
                                    </span>
                                    @break
                                @case('pending')
                                    <span class="inline-flex items-center gap-1 rounded-full border border-amber-300 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                                        <x-lucide-clock class="size-3 text-amber-600" />
                                        Pending
                                    </span>
                                    @break
                                @case('sold')
                                    <span class="inline-flex items-center gap-1 rounded-full border border-zinc-300 bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-700">
                                        <x-lucide-tag class="size-3 text-zinc-500" />
                                        Sold
                                    </span>
                                    @break
                                @default
                                    <span class="inline-flex rounded-full border border-zinc-300 px-2.5 py-0.5 text-xs font-semibold text-zinc-700">{{ $listing->status }}</span>
                            @endswitch
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $listings->links() }}</div>
@endif
