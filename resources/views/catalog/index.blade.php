@extends('layouts.app')
@section('content')
    <div class="mb-6">
        <p class="mb-1 text-xs font-bold tracking-[0.2em] text-brand uppercase">CRX Farm inventory</p>
        <h1 class="text-3xl font-black tracking-tight text-zinc-950 sm:text-4xl">Find the right part.</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-600">Search used Honda parts and complete donor cars. Message Jeremiah if you do not see what you need. New inventory is added as cars are dismantled.</p>
    </div>

    <form id="catalog-filters" class="mb-5 flex flex-wrap gap-3 rounded-lg border border-zinc-200 bg-white p-4 shadow-sm" method="GET" action="{{ route('catalog.index') }}/" role="search">
        <label class="flex min-w-56 flex-1 items-center gap-3 rounded-md border border-zinc-300 bg-white px-3 py-2.5 shadow-xs transition focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/15">
            <x-lucide-search class="size-5 shrink-0 text-zinc-400" aria-hidden="true" />
            <input class="min-w-0 grow bg-transparent text-sm text-zinc-950 outline-none placeholder:text-zinc-400" type="search" name="q" aria-label="Search catalog" placeholder="Search parts, cars, chassis..." value="{{ request('q') }}"
                list="search-suggestions"
                hx-get="{{ route('catalog.index') }}/" hx-trigger="input changed delay:400ms" hx-target="#grid"
                hx-include="#catalog-filters" hx-push-url="true">
            <datalist id="search-suggestions">
                @foreach($chassisOptions as $chassis)
                    <option value="{{ $chassis }}"></option>
                @endforeach
            </datalist>
        </label>

        <select class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm font-medium text-zinc-800 shadow-xs outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/15 sm:w-auto" name="chassis"
            hx-get="{{ route('catalog.index') }}/" hx-trigger="change" hx-target="#grid"
            hx-include="#catalog-filters" hx-push-url="true"
            aria-label="Filter by chassis">
            <option value="">All chassis</option>
            @foreach($chassisOptions as $chassis)
                <option value="{{ $chassis }}" @selected(request('chassis') === $chassis)>{{ $chassis }}</option>
            @endforeach
        </select>

        <input type="hidden" name="category" id="catalog-category-input" value="{{ request('category') }}">
        <input type="hidden" name="bolt_pattern" id="catalog-bolt-pattern-input" value="{{ request('bolt_pattern') }}">
    </form>

    <nav class="mb-4 flex flex-wrap items-center gap-2" aria-label="Filter parts by category">
        <a href="{{ route('catalog.index', array_filter(['q' => request('q'), 'chassis' => request('chassis')])) }}/"
           class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-bold tracking-wide transition {{ empty(request('category')) ? 'bg-zinc-950 text-white shadow-xs' : 'bg-white text-zinc-700 hover:bg-zinc-100 hover:text-zinc-950 ring-1 ring-zinc-200' }}">
            All categories
        </a>
        @foreach($categories as $cat)
            @php
                $preservePattern = $cat === \App\Enums\PartCategory::WheelsTires ? request('bolt_pattern') : null;
            @endphp
            <a href="{{ route('catalog.index', array_filter(['q' => request('q'), 'chassis' => request('chassis'), 'bolt_pattern' => $preservePattern, 'category' => $cat->value])) }}/"
               class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold tracking-wide transition {{ request('category') === $cat->value ? 'bg-zinc-950 text-white shadow-xs' : 'bg-white text-zinc-700 hover:bg-zinc-100 hover:text-zinc-950 ring-1 ring-zinc-200' }}">
                <x-category-icon :category="$cat->value" class="size-3.5 shrink-0" />
                {{ $cat->label() }}
            </a>
        @endforeach
    </nav>

    @if(request('category') === \App\Enums\PartCategory::WheelsTires->value || request('bolt_pattern'))
        <div class="mb-6 flex flex-wrap items-center gap-1.5 rounded-lg border border-amber-200/80 bg-amber-50/50 px-3.5 py-2.5">
            <span class="text-xs font-bold text-amber-900">Stud pattern:</span>
            <a href="{{ route('catalog.index', array_filter(['q' => request('q'), 'chassis' => request('chassis'), 'category' => request('category')])) }}/"
               class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold transition {{ empty(request('bolt_pattern')) ? 'bg-amber-700 text-white shadow-xs' : 'bg-white text-amber-900 hover:bg-amber-100 ring-1 ring-amber-200' }}">
                All patterns
            </a>
            @foreach($boltPatternOptions as $pattern)
                <a href="{{ route('catalog.index', array_filter(['q' => request('q'), 'chassis' => request('chassis'), 'category' => request('category'), 'bolt_pattern' => $pattern])) }}/"
                   class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold transition {{ request('bolt_pattern') === $pattern ? 'bg-amber-700 text-white shadow-xs' : 'bg-white text-amber-900 hover:bg-amber-100 ring-1 ring-amber-200' }}">
                    {{ $pattern }}
                </a>
            @endforeach
        </div>
    @endif

    <div id="grid" aria-live="polite">
        @include('catalog.partials.grid')
    </div>
@endsection
