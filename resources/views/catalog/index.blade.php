@extends('layouts.app')
@section('content')
  <div class="toolbar">
    <input type="text" name="q" placeholder="Search parts, cars, chassis..." value="{{ request('q') }}"
      hx-get="{{ route('catalog.index') }}" hx-trigger="keyup changed delay:400ms" hx-target="#grid"
      hx-include="[name=chassis]" hx-push-url="true">
    <select name="chassis" hx-get="{{ route('catalog.index') }}" hx-trigger="change" hx-target="#grid"
      hx-include="[name=q]" hx-push-url="true">
      <option value="">All chassis</option>
      @foreach($chassisOptions as $c)
        <option value="{{ $c }}" @selected(request('chassis') === $c)>{{ $c }}</option>
      @endforeach
    </select>
  </div>
  <div id="grid">
    @include('catalog.partials.grid')
  </div>
@endsection
