@extends('layouts.app')
@section('content')
  <h1>Admin login</h1>
  @if($errors->any())<p class="status-msg" style="background:#f3dede;border-color:#b5502e">{{ $errors->first() }}</p>@endif
  <form class="stack" method="POST" action="{{ route('admin.login') }}">
    @csrf
    <div>
      <label>Email</label>
      <input type="email" name="email" required autofocus>
    </div>
    <div>
      <label>Password</label>
      <input type="password" name="password" required>
    </div>
    <button class="btn" type="submit">Log in</button>
  </form>
@endsection
