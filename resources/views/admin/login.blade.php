@extends('layouts.app')
@section('content')
  <div class="mx-auto max-w-lg overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-zinc-200">
    <div class="h-1.5 bg-brand"></div>
    <div class="flex flex-col gap-5 p-6 sm:p-8">
      <div>
        <p class="mb-1 text-xs font-bold tracking-[0.2em] text-brand uppercase">Store management</p>
        <h1 class="text-2xl font-black tracking-tight text-zinc-950">Admin login</h1>
      </div>
      <p id="passkey-error" class="hidden rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert"></p>

      <button class="inline-flex items-center justify-center gap-2 rounded-md bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark focus:ring-2 focus:ring-brand/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50" type="button" id="passkey-login-btn">
        <x-lucide-fingerprint class="size-4.5" />
        Sign in with passkey
      </button>

      <p class="text-center">
        <a class="inline-flex items-center gap-1.5 text-sm font-semibold text-zinc-600 underline-offset-4 hover:text-brand hover:underline" href="#" id="show-pin-fallback">
          <x-lucide-key-round class="size-4 text-zinc-400" />
          Use your PIN instead
        </a>
      </p>

      <form class="hidden flex-col gap-5 border-t border-zinc-200 pt-5" method="POST" action="{{ route('admin.login.pin') }}" id="pin-fallback-form">
        @csrf
        @if($errors->any())<div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <fieldset class="flex flex-col gap-1.5">
          <legend class="mb-1.5 text-sm font-bold text-zinc-800">PIN</legend>
          <input class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 shadow-xs outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/15" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" name="pin" required autocomplete="one-time-code">
        </fieldset>
        <button class="inline-flex items-center justify-center gap-2 rounded-md bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark focus:ring-2 focus:ring-brand/30 focus:outline-none" type="submit">
          <x-lucide-lock class="size-4" />
          Sign in with PIN
        </button>
      </form>
    </div>
  </div>
@endsection

@section('scripts')
  <script src="{{ asset('js/passkey-onboarding.js') }}"></script>
  <script>
    document.getElementById('show-pin-fallback').addEventListener('click', function (e) {
      e.preventDefault();
      document.getElementById('pin-fallback-form').classList.replace('hidden', 'flex');
      this.classList.add('hidden');
    });

    document.getElementById('passkey-login-btn').addEventListener('click', async function () {
      const errorEl = document.getElementById('passkey-error');
      errorEl.classList.add('hidden');
      try {
        await window.CrxPasskeys.loginWithPasskey();
      } catch (err) {
        errorEl.textContent = err.message || 'Passkey sign-in failed.';
        errorEl.classList.remove('hidden');
      }
    });
  </script>
@endsection
