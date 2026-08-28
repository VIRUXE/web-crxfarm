@extends('layouts.app')
@section('content')
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <h1>Listings</h1>
    <div>
      <a class="btn" href="{{ route('admin.listings.create') }}">+ New listing</a>
      <form action="{{ route('admin.logout') }}" method="POST" style="display:inline">
        @csrf
        <button class="btn ghost" type="submit">Log out</button>
      </form>
    </div>
  </div>
  <table class="admin">
    <thead><tr><th>Title</th><th>Type</th><th>Chassis</th><th>Price</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @foreach($listings as $listing)
        <tr>
          <td>{{ $listing->title }}</td>
          <td>{{ $listing->type }}</td>
          <td>{{ $listing->chassis }}</td>
          <td>{{ $listing->price ?: 'ask' }}</td>
          <td>{{ $listing->status }}</td>
          <td><a href="{{ route('admin.listings.edit', $listing) }}">Edit</a></td>
        </tr>
      @endforeach
    </tbody>
  </table>
  <div class="pagination">{{ $listings->links() }}</div>
@endsection
