@extends('layouts.app')
@section('content')
  <h1>Admin login</h1>
  <p id="passkey-error" class="status-msg" style="background:#f3dede;border-color:#b5502e;display:none"></p>

  <button class="btn" type="button" id="passkey-login-btn">Sign in with passkey</button>

  <p style="margin-top:1.5rem"><a href="#" id="show-pin-fallback">Use your PIN instead</a></p>

  <form class="stack" method="POST" action="{{ route('admin.login.pin') }}" id="pin-fallback-form" style="display:none">
    @csrf
    @if($errors->any())<p class="status-msg" style="background:#f3dede;border-color:#b5502e">{{ $errors->first() }}</p>@endif
    <div>
      <label>Email</label>
      <input type="email" name="email" required>
    </div>
    <div>
      <label>PIN</label>
      <input type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" name="pin" required autocomplete="one-time-code">
    </div>
    <button class="btn" type="submit">Sign in with PIN</button>
  </form>
@endsection

@section('scripts')
  <script src="{{ asset('js/passkey-onboarding.js') }}"></script>
  <script>
    document.getElementById('show-pin-fallback').addEventListener('click', function (e) {
      e.preventDefault();
      document.getElementById('pin-fallback-form').style.display = 'block';
      this.style.display = 'none';
    });

    document.getElementById('passkey-login-btn').addEventListener('click', async function () {
      const errorEl = document.getElementById('passkey-error');
      errorEl.style.display = 'none';
      try {
        await window.CrxPasskeys.loginWithPasskey();
      } catch (err) {
        errorEl.textContent = err.message || 'Passkey sign-in failed.';
        errorEl.style.display = 'block';
      }
    });
  </script>
@endsection
