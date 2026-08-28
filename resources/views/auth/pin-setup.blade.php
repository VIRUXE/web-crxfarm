@extends('layouts.app')
@section('content')
  <h1>Set up your account</h1>
  <p>Welcome, {{ $user->name }}. First, pick a 6-digit PIN — this is only a fallback for signing in; right after this you'll set up a passkey, which is how you'll normally sign in.</p>

  @if($errors->any())<p class="status-msg" style="background:#f3dede;border-color:#b5502e">{{ $errors->first() }}</p>@endif

  <form class="stack" method="POST" action="{{ route('onboarding.pin.store', $user) }}">
    @csrf
    <div>
      <label>PIN (6 digits)</label>
      <input type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" name="pin" required autofocus autocomplete="one-time-code">
    </div>
    <div>
      <label>Confirm PIN</label>
      <input type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" name="pin_confirmation" required>
    </div>
    <button class="btn" type="submit">Continue to passkey setup</button>
  </form>
@endsection
