@if(isset($status))<p class="status-msg">{{ $status }}</p>@endif

<form class="stack" method="POST"
  action="{{ $listing->exists ? route('admin.listings.update', $listing) : route('admin.listings.store') }}"
  enctype="multipart/form-data"
  @if($listing->exists)
    hx-post="{{ route('admin.listings.update', $listing) }}" hx-target="#form-fields" hx-swap="outerHTML"
  @endif
>
  @csrf
  @if($listing->exists)@method('PUT')@endif

  <div>
    <label>Type</label>
    <select name="type">
      <option value="part" @selected($listing->type === 'part')>Individual part</option>
      <option value="car" @selected($listing->type === 'car')>Donor car</option>
    </select>
  </div>

  <div>
    <label>Title</label>
    <input type="text" name="title" value="{{ $listing->title }}" required>
  </div>

  <div>
    <label>Chassis (CRX, EF, EG, Del Sol, EK, Accord, CRV...)</label>
    <input type="text" name="chassis" value="{{ $listing->chassis }}">
  </div>

  <div>
    <label>Price (leave blank for "ask" &mdash; ranges like "$100-150" are fine)</label>
    <input type="text" name="price" value="{{ $listing->price }}">
  </div>

  <div>
    <label>Description</label>
    <textarea name="description" rows="4">{{ $listing->description }}</textarea>
  </div>

  <div>
    <label>Already pulled / missing (donor cars only)</label>
    <textarea name="missing_parts" rows="3">{{ $listing->missing_parts }}</textarea>
  </div>

  <div>
    <label>Location</label>
    <input type="text" name="location" value="{{ $listing->location ?: 'Rossville, KS' }}">
  </div>

  <div>
    <label>Status</label>
    <select name="status">
      <option value="available" @selected($listing->status === 'available')>Available</option>
      <option value="pending" @selected($listing->status === 'pending')>Pending</option>
      <option value="sold" @selected($listing->status === 'sold')>Sold</option>
    </select>
  </div>

  <div>
    <label>Add photos (no limit, select multiple)</label>
    <input type="file" name="images[]" multiple accept="image/*">
  </div>

  <button class="btn" type="submit">Save</button>
</form>

@if($listing->exists && $listing->images->isNotEmpty())
  <h3>Photos</h3>
  <div class="grid" id="image-list">
    @foreach($listing->images as $img)
      <div class="card" id="image-{{ $img->id }}">
        <div class="thumb" style="background-image:url('{{ $img->url }}')"></div>
        <div class="body">
          <button class="btn ghost" hx-delete="{{ route('admin.images.destroy', $img) }}"
            hx-target="#image-{{ $img->id }}" hx-swap="outerHTML swap:200ms"
            hx-confirm="Remove this photo?">Remove</button>
        </div>
      </div>
    @endforeach
  </div>
@endif
