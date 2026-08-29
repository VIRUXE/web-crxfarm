@extends('layouts.app')
@section('content')
  <div class="mx-auto max-w-2xl overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-zinc-200">
    <div class="h-1.5 bg-brand"></div>
    <div class="flex flex-col gap-5 p-6 sm:p-8">
      <div class="flex items-center gap-3">
        <div class="flex size-10 items-center justify-center rounded-full bg-brand/10 text-brand">
          <x-lucide-key-round class="size-5" />
        </div>
        <div>
          <p class="mb-0.5 text-xs font-bold tracking-[0.2em] text-brand uppercase">Account setup</p>
          <h1 class="text-2xl font-black tracking-tight text-zinc-950">Set up your account</h1>
        </div>
      </div>
      <p class="leading-7 text-zinc-700">Welcome, {{ $user->name }}. First, pick a 6-digit PIN. This is only a fallback for signing in; right after this you'll set up a passkey, which is how you'll normally sign in.</p>

      @if($errors->any())<div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif

      <form class="flex flex-col gap-5" method="POST" action="{{ route('onboarding.pin.store', $user) }}">
        @csrf
        <fieldset class="flex flex-col gap-1.5">
          <legend class="mb-1.5 text-sm font-bold text-zinc-800">PIN (6 digits)</legend>
          <input class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 shadow-xs outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/15" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" name="pin" required autofocus autocomplete="one-time-code">
        </fieldset>
        <fieldset class="flex flex-col gap-1.5">
          <legend class="mb-1.5 text-sm font-bold text-zinc-800">Confirm PIN</legend>
          <input class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 shadow-xs outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/15" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" name="pin_confirmation" required>
        </fieldset>
        <button class="inline-flex self-start items-center justify-center gap-2 rounded-md bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark focus:ring-2 focus:ring-brand/30 focus:outline-none" type="submit">
          <span>Continue to passkey setup</span>
          <x-lucide-arrow-right class="size-4" />
        </button>
      </form>
    </div>
  </div>
@endsection
