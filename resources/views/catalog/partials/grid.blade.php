@if($listings->isEmpty())
  <p class="empty">Nothing matches that search yet &mdash; try a different chassis or keyword.</p>
@else
  <div class="grid">
    @foreach($listings as $listing)
      <a class="card" href="{{ route('catalog.show', $listing) }}">
        <div class="thumb" @if($listing->images->first()) style="background-image:url('{{ $listing->images->first()->url }}')" @endif>
          @unless($listing->images->first()) no photo yet @endunless
        </div>
        <div class="body">
          @if($listing->type === 'car')<span class="badge car">Donor car</span>@else<span class="badge">Part</span>@endif
          <span class="title">{{ $listing->title }}</span>
          @if($listing->chassis)<span class="chassis">{{ $listing->chassis }}</span>@endif
          <span class="price">{{ $listing->price ?: 'Ask for price' }}</span>
        </div>
      </a>
    @endforeach
  </div>
  <div class="pagination">{{ $listings->links() }}</div>
@endif
