@extends('layouts.app')
@section('content')
  <p><a href="{{ route('catalog.index') }}">&larr; back to catalog</a></p>
  <div class="detail">
    <div class="photos">
      @forelse($listing->images as $img)
        <img src="{{ $img->url }}" alt="{{ $listing->title }}">
      @empty
        <div class="thumb" style="aspect-ratio:4/3">no photos yet</div>
      @endforelse
    </div>
    <div>
      @if($listing->type === 'car')<span class="badge car">Donor car</span>@else<span class="badge">Part</span>@endif
      <h1>{{ $listing->title }}</h1>
      @if($listing->chassis)<p class="chassis">{{ $listing->chassis }}</p>@endif
      <p class="price" style="font-size:1.4rem">{{ $listing->price ?: 'Ask for price' }}</p>
      @if($listing->location)<p>&#128205; {{ $listing->location }}</p>@endif

      @if($listing->description)
        <p>{{ $listing->description }}</p>
      @endif

      @if($listing->type === 'car' && $listing->missing_parts)
        <h3>Already pulled / missing from this car</h3>
        <p>{{ $listing->missing_parts }}</p>
        <p>Everything else is likely still on the car &mdash; ask and Jeremiah will confirm.</p>
      @endif

      <p>
        <a class="btn rust" href="https://www.facebook.com/jeremiah.freeman.116318" target="_blank" rel="noopener">
          Ask about this on Messenger
        </a>
      </p>
    </div>
  </div>
@endsection
