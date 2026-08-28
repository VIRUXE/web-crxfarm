@extends('layouts.app')
@section('content')
  <h1>Set up your passkey</h1>
  <p>One last step. Set up a passkey (Face ID, Touch ID, Windows Hello, or a security key) on this device — it's how you'll sign in from now on. Your PIN only works as a fallback once this is done.</p>

  <p id="enroll-error" class="status-msg" style="background:#f3dede;border-color:#b5502e;display:none"></p>

  <button class="btn" type="button" id="enroll-btn">Set up passkey</button>
@endsection

@section('scripts')
  <script src="{{ asset('js/passkey-onboarding.js') }}"></script>
  <script>
    document.getElementById('enroll-btn').addEventListener('click', async function () {
      const errorEl = document.getElementById('enroll-error');
      errorEl.style.display = 'none';
      this.disabled = true;
      try {
        await window.CrxPasskeys.registerPasskey('Primary device');
        window.location.href = '{{ route('admin.listings.index') }}';
      } catch (err) {
        errorEl.textContent = err.message || 'Passkey setup failed. Try again.';
        errorEl.style.display = 'block';
        this.disabled = false;
      }
    });
  </script>
@endsection
