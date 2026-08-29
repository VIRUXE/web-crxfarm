@extends('layouts.app')
@section('content')
  <p class="mb-1 text-xs font-bold tracking-[0.2em] text-brand uppercase">Store management</p>
  <h1 class="mb-6 text-3xl font-black tracking-tight text-zinc-950">Invite a user</h1>

  @if(session('invite_link'))
    <div class="mb-5 flex flex-col gap-2 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-800" role="status">
      <div class="flex items-center gap-2 font-bold text-emerald-950">
        <x-lucide-check-circle-2 class="size-4.5 shrink-0 text-emerald-600" />
        <span>Invite created for {{ session('invited_username') }}</span>
      </div>
      <p>There's no email or any other delivery. Copy this link and hand it to them directly (text, in person, however):</p>
      <code class="my-1 block break-all rounded bg-emerald-100 p-2 font-mono text-emerald-950">{{ session('invite_link') }}</code>
      <p class="text-xs text-emerald-700">It expires in 48 hours and is for one-time use. They'll set a PIN, then a passkey is required before the account is active.</p>
    </div>
  @endif

  @if($errors->any())<div class="mb-5 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif

  <form class="flex max-w-2xl flex-col gap-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm sm:p-6" method="POST" action="{{ route('admin.users.invite.store') }}">
    @csrf
    <fieldset class="flex flex-col gap-1.5">
      <legend class="mb-1.5 text-sm font-bold text-zinc-800">Name</legend>
      <input class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 shadow-xs outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/15" type="text" name="name" required autofocus>
    </fieldset>
    <fieldset class="flex flex-col gap-1.5">
      <legend class="mb-1.5 text-sm font-bold text-zinc-800">Username</legend>
      <input class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 shadow-xs outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/15" type="text" name="username" pattern="[A-Za-z0-9_-]+" required>
    </fieldset>
    <button class="inline-flex self-start items-center justify-center gap-2 rounded-md bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark focus:ring-2 focus:ring-brand/30 focus:outline-none" type="submit">
      <x-lucide-user-plus class="size-4" />
      Create invite link
    </button>
  </form>
@endsection
