@if(isset($status))
  <div class="mb-5 flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">
    <x-lucide-check-circle-2 class="size-4 shrink-0 text-emerald-600" />
    <span>{{ $status }}</span>
  </div>
@endif

<form class="flex max-w-2xl flex-col gap-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm sm:p-6" method="POST"
  action="{{ $listing->exists ? route('admin.listings.update', $listing) : route('admin.listings.store') }}"
  enctype="multipart/form-data"
  @if($listing->exists)
    hx-post="{{ route('admin.listings.update', $listing) }}" hx-target="#form-fields" hx-swap="outerHTML"
  @endif
>
  @csrf
  @if($listing->exists)@method('PUT')@endif

  <fieldset class="flex flex-col gap-1.5">
    <legend class="mb-1.5 text-sm font-bold text-zinc-800">Type</legend>
    <div class="grid grid-cols-2 gap-2">
      @foreach(\App\Enums\ListingType::cases() as $type)
        <label class="flex cursor-pointer flex-col items-center gap-1.5 rounded-md border border-zinc-300 bg-white px-3 py-3 text-xs font-semibold text-zinc-500 shadow-xs transition hover:border-zinc-400 has-checked:border-brand has-checked:bg-brand/5 has-checked:text-brand-dark">
          <input class="sr-only" type="radio" name="type" value="{{ $type->value }}" onchange="window.updateListingTypeFields(this.form)" @checked(old('type', $listing->type?->value ?? $listing->type ?? \App\Enums\ListingType::Part->value) === $type->value)>
          @if($type->isPart())
            <x-lucide-wrench class="size-6 text-brand" aria-hidden="true" />
          @else
            <x-lucide-car class="size-6 text-brand" aria-hidden="true" />
          @endif
          {{ $type->label() }}
        </label>
      @endforeach
    </div>
    <p class="text-sm text-zinc-500">Choose whether this is an individual part or a complete donor car.</p>
  </fieldset>

  <fieldset class="flex flex-col gap-1.5">
    <legend class="mb-1.5 text-sm font-bold text-zinc-800">Title</legend>
    <input class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 shadow-xs outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/15"
      type="text"
      name="title"
      list="part-suggestions"
      autocomplete="off"
      placeholder="e.g. CRX Si Sunroof Panel or 1991 CRX Si Shell..."
      value="{{ old('title', $listing->title) }}"
      required>
    <datalist id="part-suggestions">
      @foreach(\App\Models\Listing::partSuggestions() as $partOption)
        <option value="{{ $partOption }}"></option>
      @endforeach
    </datalist>
  </fieldset>

  <fieldset class="flex flex-col gap-1.5" id="chassis-car-section">
    <legend class="mb-1.5 text-sm font-bold text-zinc-800">Chassis</legend>
    <input class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 shadow-xs outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/15"
      type="text"
      name="chassis"
      id="chassis-input"
      list="chassis-suggestions"
      autocomplete="off"
      placeholder="e.g. CRX, EF, EG, Del Sol..."
      value="{{ old('chassis', $listing->chassis) }}">
    <datalist id="chassis-suggestions">
      @foreach(\App\Models\Listing::chassisSuggestions() as $chassisOption)
        <option value="{{ $chassisOption }}"></option>
      @endforeach
    </datalist>
    <div class="mt-1 flex flex-wrap items-center gap-1.5">
      <span class="text-xs text-zinc-400">Quick pick:</span>
      @foreach(['CRX', 'EF', 'EG', 'Del Sol', 'EK', 'DA Integra', 'DC2 Integra', 'Accord', 'CR-V'] as $quickChassis)
        <button type="button"
          class="inline-flex cursor-pointer items-center rounded-sm bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-200 transition"
          onclick="document.getElementById('chassis-input').value='{{ $quickChassis }}'">
          {{ $quickChassis }}
        </button>
      @endforeach
    </div>
    <p class="text-sm text-zinc-500">Type for instant autocomplete or tap a quick pick button above.</p>
  </fieldset>

  <fieldset class="flex flex-col gap-1.5" id="chassis-part-section">
    <legend class="mb-1.5 text-sm font-bold text-zinc-800">Compatible chassis</legend>
    @php
      $selectedChassisIds = old('chassis_ids', $listing->compatibleChassis->pluck('id')->all());
    @endphp
    <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-3">
      @foreach($chassisOptions as $chassisOption)
        <label class="flex cursor-pointer items-center gap-1.5 rounded-md border border-zinc-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-zinc-600 shadow-xs transition hover:border-zinc-400 has-checked:border-brand has-checked:bg-brand/5 has-checked:text-brand-dark">
          <input type="checkbox" name="chassis_ids[]" value="{{ $chassisOption->id }}" @checked(in_array($chassisOption->id, $selectedChassisIds))>
          {{ $chassisOption->name }}
        </label>
      @endforeach
    </div>
    <input class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 shadow-xs outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/15"
      type="text"
      name="chassis_other"
      autocomplete="off"
      placeholder="Other chassis, comma separated">
    <p class="text-sm text-zinc-500">Select every chassis this part fits. Add anything not listed above.</p>
  </fieldset>

  <fieldset class="flex flex-col gap-1.5" id="category-section">
    <legend class="mb-1.5 text-sm font-bold text-zinc-800">Category</legend>
    <select class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 shadow-xs outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/15" name="category">
      <option value="">No category / Not applicable</option>
      @foreach(\App\Enums\PartCategory::cases() as $category)
        <option value="{{ $category->value }}" @selected(old('category', $listing->category?->value ?? $listing->category) === $category->value)>{{ $category->label() }}</option>
      @endforeach
    </select>
    <p class="text-sm text-zinc-500">Select a category for individual parts.</p>
  </fieldset>

  <fieldset class="flex flex-col gap-1.5" id="bolt-pattern-section">
    <legend class="mb-1.5 text-sm font-bold text-zinc-800">Bolt / Stud pattern</legend>
    <input class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 shadow-xs outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/15"
      type="text"
      name="bolt_pattern"
      id="bolt-pattern-input"
      list="bolt-pattern-suggestions"
      autocomplete="off"
      placeholder="e.g. 4x100 or 5x114.3"
      value="{{ old('bolt_pattern', $listing->bolt_pattern) }}">
    <datalist id="bolt-pattern-suggestions">
      @foreach(\App\Models\Listing::standardBoltPatterns() as $patternOption)
        <option value="{{ $patternOption }}"></option>
      @endforeach
      <option value="4x100, 4x114.3"></option>
    </datalist>
    <div class="mt-1 flex flex-wrap items-center gap-1.5">
      <span class="text-xs text-zinc-400">Quick pick:</span>
      @foreach(['4x100', '4x114.3', '5x114.3', '5x120', '4x100, 4x114.3'] as $quickPattern)
        <button type="button"
          class="inline-flex cursor-pointer items-center rounded-sm bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-200 transition"
          onclick="document.getElementById('bolt-pattern-input').value='{{ $quickPattern }}'">
          {{ $quickPattern }}
        </button>
      @endforeach
      <button type="button"
        class="inline-flex cursor-pointer items-center rounded-sm bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500 hover:bg-zinc-200 transition"
        onclick="document.getElementById('bolt-pattern-input').value=''">
        Clear
      </button>
    </div>
    <p class="text-sm text-zinc-500">For wheels and rims. 4x100 for Civic/CRX/Del Sol/Fit; 4x114.3 for Accord/Prelude; 5x114.3 for ITR/RSX/CR-V/S2000.</p>
  </fieldset>

  <fieldset class="flex flex-col gap-1.5">
    <legend class="mb-1.5 text-sm font-bold text-zinc-800">Price</legend>
    <div class="relative">
      <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400">
        <x-lucide-dollar-sign class="size-4" aria-hidden="true" />
      </span>
      <input class="w-full rounded-md border border-zinc-300 bg-white py-2.5 pr-3 pl-9 text-zinc-900 shadow-xs outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/15" type="text" inputmode="decimal" name="price" placeholder="150 or 100-150" value="{{ old('price', $listing->price) }}">
    </div>
    <p class="text-sm text-zinc-500">Leave blank for "ask." Ranges like "100-150" are fine.</p>
  </fieldset>

  <fieldset class="flex flex-col gap-1.5">
    <legend class="mb-1.5 text-sm font-bold text-zinc-800">Description</legend>
    <textarea class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 shadow-xs outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/15" name="description" rows="4">{{ $listing->description }}</textarea>
  </fieldset>

  <fieldset class="flex flex-col gap-2" id="missing-parts-section">
    <div class="flex items-center justify-between">
      <legend class="text-sm font-bold text-zinc-800">Already pulled / missing parts</legend>
      <span class="text-xs text-zinc-400">Donor cars only</span>
    </div>
    <p id="help-missing-parts" class="text-xs text-zinc-500">Add each part that has already been stripped or is missing from this car.</p>

    <div id="missing-parts-container" class="flex flex-col gap-2" role="group" aria-describedby="help-missing-parts">
      @php
        $rawItems = old('missing_parts', $listing->missingPartsList());
        if (is_string($rawItems)) {
            $rawItems = (new \App\Models\Listing(['missing_parts' => $rawItems]))->missingPartsList();
        }
        $missingItems = is_array($rawItems) ? array_values(array_filter($rawItems, fn ($i) => $i !== null && $i !== '')) : [];
      @endphp

      @forelse($missingItems as $item)
        <div class="flex items-center gap-2 missing-part-row">
          <input
            type="text"
            name="missing_parts[]"
            list="part-suggestions"
            autocomplete="off"
            value="{{ $item }}"
            placeholder="e.g. Hood, Driver seat, ECU..."
            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/15"
          >
          <button
            type="button"
            class="inline-flex shrink-0 items-center justify-center rounded-md border border-zinc-300 bg-white p-2 text-zinc-500 shadow-xs hover:border-red-300 hover:bg-red-50 hover:text-brand transition cursor-pointer"
            onclick="this.closest('.missing-part-row').remove()"
            aria-label="Remove item"
            title="Remove item"
          >
            <x-lucide-x class="size-4" />
          </button>
        </div>
      @empty
        <div class="flex items-center gap-2 missing-part-row">
          <input
            type="text"
            name="missing_parts[]"
            list="part-suggestions"
            autocomplete="off"
            value=""
            placeholder="e.g. Hood, Driver seat, ECU..."
            class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none transition placeholder:text-zinc-400 focus:border-brand focus:ring-2 focus:ring-brand/15"
          >
          <button
            type="button"
            class="inline-flex shrink-0 items-center justify-center rounded-md border border-zinc-300 bg-white p-2 text-zinc-500 shadow-xs hover:border-red-300 hover:bg-red-50 hover:text-brand transition cursor-pointer"
            onclick="this.closest('.missing-part-row').remove()"
            aria-label="Remove item"
            title="Remove item"
          >
            <x-lucide-x class="size-4" />
          </button>
        </div>
      @endforelse
    </div>

    <div class="mt-1 flex flex-wrap items-center gap-1.5">
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-md border border-zinc-300 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700 shadow-xs hover:bg-zinc-100 hover:text-zinc-900 transition cursor-pointer"
        onclick="addMissingPartRow()"
      >
        <x-lucide-plus class="size-3.5 text-zinc-500" />
        Add part
      </button>

      <span class="text-xs text-zinc-400 ml-1">Quick add:</span>
      @foreach(['Hood', 'Front Bumper', 'Driver Seat', 'ECU', 'Engine', 'Transmission', 'Wiring Harness'] as $quickPart)
        <button type="button"
          class="inline-flex cursor-pointer items-center rounded-sm bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-200 transition"
          onclick="addMissingPartRow('{{ $quickPart }}')">
          + {{ $quickPart }}
        </button>
      @endforeach
    </div>
  </fieldset>

  <fieldset class="flex flex-col gap-1.5">
    <legend class="mb-1.5 text-sm font-bold text-zinc-800">Status</legend>
    <div class="grid grid-cols-3 gap-2">
      @php
        $statusOptions = [
          'available' => ['label' => 'Available', 'ring' => 'has-checked:border-emerald-400 has-checked:bg-emerald-50 has-checked:text-emerald-700', 'icon' => 'text-emerald-500'],
          'pending' => ['label' => 'Pending', 'ring' => 'has-checked:border-amber-400 has-checked:bg-amber-50 has-checked:text-amber-700', 'icon' => 'text-amber-500'],
          'sold' => ['label' => 'Sold', 'ring' => 'has-checked:border-zinc-400 has-checked:bg-zinc-100 has-checked:text-zinc-700', 'icon' => 'text-zinc-500'],
        ];
      @endphp
      @foreach($statusOptions as $value => $option)
        <label class="flex cursor-pointer flex-col items-center gap-1.5 rounded-md border border-zinc-300 bg-white px-3 py-3 text-xs font-semibold text-zinc-500 shadow-xs transition hover:border-zinc-400 {{ $option['ring'] }}">
          <input class="sr-only" type="radio" name="status" value="{{ $value }}" @checked(old('status', $listing->status ?: 'available') === $value)>
          @switch($value)
            @case('available')
              <x-lucide-check-circle-2 class="size-6 {{ $option['icon'] }}" aria-hidden="true" />
              @break
            @case('pending')
              <x-lucide-clock class="size-6 {{ $option['icon'] }}" aria-hidden="true" />
              @break
            @case('sold')
              <x-lucide-tag class="size-6 {{ $option['icon'] }}" aria-hidden="true" />
              @break
          @endswitch
          {{ $option['label'] }}
        </label>
      @endforeach
    </div>
  </fieldset>

  <fieldset class="flex flex-col gap-1.5">
    <legend class="mb-1.5 text-sm font-bold text-zinc-800">Add photos</legend>
    <input class="block w-full rounded-md border border-zinc-300 bg-white text-sm text-zinc-700 shadow-xs file:mr-4 file:border-0 file:bg-zinc-100 file:px-4 file:py-2.5 file:font-semibold file:text-zinc-800 hover:file:bg-zinc-200 focus:ring-2 focus:ring-brand/15 focus:outline-none" type="file" name="images[]" multiple accept="image/*">
    <p class="text-sm text-zinc-500">No limit; select multiple files if needed.</p>
  </fieldset>

  <fieldset class="flex flex-col gap-1.5">
    <legend class="mb-1.5 text-sm font-bold text-zinc-800">Add videos</legend>
    <input class="block w-full rounded-md border border-zinc-300 bg-white text-sm text-zinc-700 shadow-xs file:mr-4 file:border-0 file:bg-zinc-100 file:px-4 file:py-2.5 file:font-semibold file:text-zinc-800 hover:file:bg-zinc-200 focus:ring-2 focus:ring-brand/15 focus:outline-none" type="file" name="videos[]" multiple accept="video/*">
    <p class="text-sm text-zinc-500">MP4/MOV/WebM up to 256MB each. Converted to WebM with a watermark on save.</p>
    @error('videos.*')<p class="text-sm font-semibold text-brand">{{ $message }}</p>@enderror
  </fieldset>

  <button class="inline-flex self-start items-center justify-center gap-1.5 rounded-md bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark focus:ring-2 focus:ring-brand/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50" type="submit">
    <x-lucide-check class="size-4" />
    Save listing
  </button>
</form>

@if($listing->exists && $listing->videos->isNotEmpty())
  <h2 class="mt-8 mb-4 text-xl font-bold">Videos</h2>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($listing->videos as $vid)
      <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-zinc-200" id="video-{{ $vid->id }}">
        <video class="aspect-4/3 w-full bg-black object-contain" controls preload="none" @if($vid->poster_url) poster="{{ $vid->poster_url }}" @endif>
          <source src="{{ $vid->url }}" type="video/webm">
        </video>
        <div class="p-4">
          <button class="inline-flex items-center justify-center gap-1.5 rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-sm font-semibold text-zinc-700 shadow-sm transition hover:border-red-300 hover:bg-red-50 hover:text-brand focus:ring-2 focus:ring-brand/20 focus:outline-none" hx-delete="{{ route('admin.videos.destroy', $vid) }}"
            hx-target="#video-{{ $vid->id }}" hx-swap="outerHTML swap:200ms"
            hx-confirm="Remove this video?">
            <x-lucide-trash-2 class="size-4 text-red-600" />
            Remove
          </button>
        </div>
      </div>
    @endforeach
  </div>
@endif

@include('admin.listings.partials.images-grid', ['listing' => $listing])
