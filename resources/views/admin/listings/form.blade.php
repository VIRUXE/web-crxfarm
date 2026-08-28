@extends('layouts.app')
@section('content')
  <p><a href="{{ route('admin.listings.index') }}">&larr; all listings</a></p>
  <h1>{{ $listing->exists ? 'Edit listing' : 'New listing' }}</h1>
  <div id="form-fields">
    @include('admin.listings.partials.form-fields')
  </div>
@endsection
