@extends('layouts.app')
@section('content')
  <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
      <p class="mb-1 text-xs font-bold tracking-[0.2em] text-brand uppercase">Store management</p>
      <h1 class="text-3xl font-black tracking-tight text-zinc-950">Invite a user</h1>
    </div>
    <a class="inline-flex items-center justify-center gap-1.5 rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 focus:ring-2 focus:ring-brand/20 focus:outline-none" href="{{ route('admin.users.index') }}">
      <x-lucide-arrow-left class="size-4 text-zinc-500" />
      Users
    </a>
  </div>

  @if(session('invited_pin'))
    <div class="mb-5 flex flex-col gap-3 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-800" role="status">
      <div class="flex items-center gap-2 font-bold text-emerald-950">
        <x-lucide-check-circle-2 class="size-4.5 shrink-0 text-emerald-600" />
        <span>Account created for {{ session('invited_username') }}</span>
      </div>
      <p>Give them this PIN directly — it's shown once and there's no email or other delivery. They sign in with it at the admin login (“Use your PIN instead”), then set up a passkey.</p>
      <div class="my-1 flex items-center gap-3">
        <code class="rounded bg-emerald-100 px-4 py-2 font-mono text-2xl font-black tracking-[0.3em] text-emerald-950">{{ session('invited_pin') }}</code>
      </div>
      <p class="text-xs text-emerald-700">This PIN won't be shown again. If it's lost, set a new one from the <a class="font-semibold underline underline-offset-2" href="{{ route('admin.users.index') }}">Users</a> page.</p>
    </div>
  @endif

  @if($errors->any())<div class="mb-5 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif

  <form class="flex max-w-2xl flex-col gap-5 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm sm:p-6" method="POST" action="{{ route('admin.users.invite.store') }}">
    @csrf
    <fieldset class="flex flex-col gap-1.5">
      <legend class="mb-1.5 text-sm font-bold text-zinc-800">Username</legend>
      <input class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-zinc-900 shadow-xs outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/15" type="text" name="username" pattern="[A-Za-z0-9_-]+" required autofocus>
      <p class="text-xs text-zinc-500">Letters, numbers, dashes and underscores. This is how they sign in and how their passkey is labelled.</p>
    </fieldset>
    <button class="inline-flex self-start items-center justify-center gap-2 rounded-md bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark focus:ring-2 focus:ring-brand/30 focus:outline-none" type="submit">
      <x-lucide-user-plus class="size-4" />
      Create account &amp; PIN
    </button>
  </form>
@endsection
