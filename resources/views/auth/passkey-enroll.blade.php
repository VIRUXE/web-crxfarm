@extends('layouts.app')
@php($additional = $isAdditionalDevice ?? false)
@section('content')
  <div class="mx-auto max-w-2xl overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-zinc-200">
    <div class="h-1.5 bg-brand"></div>
    <div class="flex flex-col gap-5 p-6 sm:p-8">
      <div class="flex items-center gap-3">
        <div class="flex size-10 items-center justify-center rounded-full bg-brand/10 text-brand">
          <x-lucide-shield-check class="size-5" />
        </div>
        <div>
          <p class="mb-0.5 text-xs font-bold tracking-[0.2em] text-brand uppercase">{{ $additional ? 'New device' : 'Account setup' }}</p>
          <h1 class="text-2xl font-black tracking-tight text-zinc-950">{{ $additional ? 'Set up this device' : 'Set up your passkey' }}</h1>
        </div>
      </div>
      @if($additional)
        <p class="leading-7 text-zinc-700">This device doesn't have a passkey yet. Add one now and you'll sign in from here with it directly — no PIN needed next time.</p>
      @else
        <p class="leading-7 text-zinc-700">One last step. Set up a passkey (Face ID, Touch ID, Windows Hello, or a security key) on this device. From now on it's how you sign in. On any new device, sign in with your PIN once and you'll be brought right back here to add a passkey for it.</p>
      @endif

      <p id="enroll-error" class="hidden rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert"></p>

      <fieldset class="flex flex-col gap-1.5">
        <legend class="mb-1.5 text-sm font-bold text-zinc-800">Name this passkey <span class="font-normal text-zinc-400">(optional)</span></legend>
        <input class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 shadow-xs outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/15" type="text" id="passkey-name" maxlength="60" autocomplete="off" placeholder="{{ $additional ? 'e.g. iPhone, work laptop' : 'e.g. MacBook, YubiKey' }}">
        <p class="text-xs text-zinc-500">Helps you tell your passkeys apart later. Leave blank to use a default.</p>
      </fieldset>

      <div>
        <button class="inline-flex items-center justify-center gap-2 rounded-md bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark focus:ring-2 focus:ring-brand/30 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50" type="button" id="enroll-btn">
          <x-lucide-fingerprint class="size-4.5" />
          {{ $additional ? 'Add passkey for this device' : 'Set up passkey' }}
        </button>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script src="{{ asset('js/passkey-onboarding.js') }}"></script>
  <script>
    const PASSKEY_NAME = @json($additional ? 'Added device ('.now()->format('M j, Y').')' : 'Primary device');
    document.getElementById('enroll-btn').addEventListener('click', async function () {
      const errorEl = document.getElementById('enroll-error');
      errorEl.classList.add('hidden');
      this.disabled = true;
      try {
        const typed = document.getElementById('passkey-name').value.trim();
        await window.CrxPasskeys.registerPasskey(typed || PASSKEY_NAME);
        window.location.href = '{{ route('admin.listings.index') }}';
      } catch (err) {
        errorEl.textContent = err.message || 'Passkey setup failed. Try again.';
        errorEl.classList.remove('hidden');
        this.disabled = false;
      }
    });
  </script>
@endsection
