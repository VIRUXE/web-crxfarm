@extends('layouts.app')
@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="mb-1 text-xs font-bold tracking-[0.2em] text-brand uppercase">Store management</p>
            <h1 class="text-3xl font-black tracking-tight text-zinc-950">Listings</h1>
        </div>
        <div class="flex gap-2">
            <a class="inline-flex items-center justify-center gap-1.5 rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 focus:ring-2 focus:ring-brand/20 focus:outline-none" href="{{ route('admin.images.index') }}">
                <x-lucide-images class="size-4 text-zinc-500" />
                Photos
            </a>
            <a class="inline-flex items-center justify-center gap-1.5 rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 focus:ring-2 focus:ring-brand/20 focus:outline-none" href="{{ route('admin.users.index') }}">
                <x-lucide-users class="size-4 text-zinc-500" />
                Users
            </a>
            <a class="inline-flex items-center justify-center gap-1.5 rounded-md bg-brand px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark focus:ring-2 focus:ring-brand/30 focus:outline-none" href="{{ route('admin.listings.create') }}">
                <x-lucide-plus class="size-4" />
                New listing
            </a>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button class="inline-flex items-center justify-center gap-1.5 rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 focus:ring-2 focus:ring-brand/20 focus:outline-none" type="submit">
                    <x-lucide-log-out class="size-4 text-zinc-500" />
                    Log out
                </button>
            </form>
        </div>
    </div>

    <form id="listing-search" class="mb-5" method="GET" action="{{ route('admin.listings.index') }}" role="search">
        <label class="flex items-center gap-3 rounded-md border border-zinc-300 bg-white px-3 py-2.5 shadow-xs transition focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/15">
            <x-lucide-search class="size-5 shrink-0 text-zinc-400" aria-hidden="true" />
            <input class="min-w-0 grow bg-transparent text-sm text-zinc-950 outline-none placeholder:text-zinc-400" type="search" name="q" aria-label="Search listings" placeholder="Search title, chassis, description..." value="{{ request('q') }}"
                hx-get="{{ route('admin.listings.index') }}" hx-trigger="input changed delay:400ms, search" hx-target="#listing-results"
                hx-include="#listing-search" hx-push-url="true">
        </label>
    </form>

    <div id="listing-results" aria-live="polite">
        @include('admin.listings.partials.table')
    </div>
@endsection
