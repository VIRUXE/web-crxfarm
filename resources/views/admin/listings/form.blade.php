@extends('layouts.app')
@section('content')
    <a class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-zinc-600 transition hover:text-brand" href="{{ route('admin.listings.index') }}">
        <x-lucide-arrow-left class="size-4" />
        All listings
    </a>
    <p class="mb-1 text-xs font-bold tracking-[0.2em] text-brand uppercase">Store management</p>
    <h1 class="mb-6 text-3xl font-black tracking-tight text-zinc-950">{{ $listing->exists ? 'Edit listing' : 'New listing' }}</h1>
    <div id="form-fields">
        @include('admin.listings.partials.form-fields')
    </div>
@endsection
