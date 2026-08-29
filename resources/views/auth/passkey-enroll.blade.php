@extends('layouts.app')
@section('content')
  <div class="mx-auto max-w-2xl overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-zinc-200">
    <div class="h-1.5 bg-brand"></div>
    <div class="flex flex-col gap-5 p-6 sm:p-8">
      <div class="flex items-center gap-3">
        <div class="flex size-10 items-center justify-center rounded-full bg-brand/10 text-brand">
          <x-lucide-shield-check class="size-5" />
        </div>
        <div>
          <p class="mb-0.5 text-xs font-bold tracking-[0.2em] text-brand uppercase">Account setup</p>
          <h1 class="text-2xl font-black tracking-tight text-zinc-950">Set up your passkey</h1>
        </div>
      </div>
      <p class="leading-7 text-zinc-700">One last step. Set up a passkey (Face ID, Touch ID, Windows Hello, or a security key) on this device. It's how you'll sign in from now on. Your PIN only works as a fallback once this is done.</p>

      <p id="enroll-error" class="hidden rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert"></p>

      <div>
        <button class="inline-flex items-center justify-center gap-2 rounded-md bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark focus:ring-2 focus:ring-brand/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50" type="button" id="enroll-btn">
          <x-lucide-fingerprint class="size-4.5" />
          Set up passkey
        </button>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script src="{{ asset('js/passkey-onboarding.js') }}"></script>
  <script>
    document.getElementById('enroll-btn').addEventListener('click', async function () {
      const errorEl = document.getElementById('enroll-error');
      errorEl.classList.add('hidden');
      this.disabled = true;
      try {
        await window.CrxPasskeys.registerPasskey('Primary device');
        window.location.href = '{{ route('admin.listings.index') }}';
      } catch (err) {
        errorEl.textContent = err.message || 'Passkey setup failed. Try again.';
        errorEl.classList.remove('hidden');
        this.disabled = false;
      }
    });
  </script>
@endsection
