@extends('layouts.app')
@section('content')
  <h1>Invite a user</h1>

  @if(session('invite_link'))
    <p class="status-msg">
      Invite created for <strong>{{ session('invited_username') }}</strong>. There's no email or
      any other delivery — copy this link and hand it to them directly (text, in person,
      however):<br>
      <code style="word-break:break-all">{{ session('invite_link') }}</code>
      — expires in 48 hours, one-time use. They'll set a PIN, then a passkey is required
      before the account is active.
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
      <label>Username</label>
      <input type="text" name="username" pattern="[A-Za-z0-9_-]+" required>
    </div>
    <button class="btn" type="submit">Create invite link</button>
  </form>
@endsection
