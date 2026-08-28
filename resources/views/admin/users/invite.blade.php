@extends('layouts.app')
@section('content')
  <h1>Invite a user</h1>

  @if(session('invite_link'))
    <p class="status-msg">
      Invite created for {{ session('invited_email') }}. Mail isn't configured yet
      (it just goes to the server log), so send this link directly:<br>
      <code style="word-break:break-all">{{ session('invite_link') }}</code>
      — expires in 48 hours, one-time use.
    </p>
  @endif

  @if($errors->any())<p class="status-msg" style="background:#f3dede;border-color:#b5502e">{{ $errors->first() }}</p>@endif

  <form class="stack" method="POST" action="{{ route('admin.users.invite.store') }}">
    @csrf
    <div>
      <label>Name</label>
      <input type="text" name="name" required autofocus>
    </div>
    <div>
      <label>Email</label>
      <input type="email" name="email" required>
    </div>
    <button class="btn" type="submit">Send invite</button>
  </form>
@endsection
