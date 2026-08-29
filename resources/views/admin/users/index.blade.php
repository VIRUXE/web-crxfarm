@extends('layouts.app')
@section('content')
  <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
      <p class="mb-1 text-xs font-bold tracking-[0.2em] text-brand uppercase">Store management</p>
      <h1 class="text-3xl font-black tracking-tight text-zinc-950">Users</h1>
    </div>
    <div class="flex gap-2">
      <a class="inline-flex items-center justify-center gap-1.5 rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 focus:ring-2 focus:ring-brand/20 focus:outline-none" href="{{ route('admin.listings.index') }}">
        <x-lucide-arrow-left class="size-4 text-zinc-500" />
        Listings
      </a>
      <a class="inline-flex items-center justify-center gap-1.5 rounded-md bg-brand px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-brand-dark focus:ring-2 focus:ring-brand/30 focus:outline-none" href="{{ route('admin.users.invite.create') }}">
        <x-lucide-user-plus class="size-4" />
        Invite user
      </a>
    </div>
  </div>

  @if($errors->any())<div class="mb-5 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>@endif

  <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
      <thead class="bg-zinc-50 text-xs tracking-wider text-zinc-500 uppercase">
        <tr>
          <th class="px-4 py-3 font-bold">User</th>
          <th class="px-4 py-3 font-bold">Role</th>
          <th class="px-4 py-3 font-bold">Status</th>
          <th class="px-4 py-3 font-bold">Passkeys</th>
          <th class="px-4 py-3 text-right font-bold">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-zinc-100">
        @foreach($users as $user)
          <tr class="align-middle">
            <td class="px-4 py-3">
              <div class="font-semibold text-zinc-900">{{ $user->name }}
                @if($user->is(auth()->user()))<span class="ml-1 text-xs font-medium text-zinc-400">(you)</span>@endif
              </div>
              <div class="text-xs text-zinc-500">&#64;{{ $user->username }}</div>
            </td>
            <td class="px-4 py-3">
              @if($user->is_admin)
                <span class="inline-flex items-center gap-1 rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-bold text-brand"><x-lucide-shield class="size-3" /> Admin</span>
              @else
                <span class="text-xs text-zinc-500">Member</span>
              @endif
            </td>
            <td class="px-4 py-3">
              @php $badges = [
                \App\Models\User::STATUS_ACTIVE => ['Active', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                \App\Models\User::STATUS_PIN_SET => ['PIN set — no passkey', 'bg-amber-50 text-amber-700 border-amber-200'],
                \App\Models\User::STATUS_INVITED => ['Invited', 'bg-zinc-100 text-zinc-600 border-zinc-200'],
              ]; [$label, $classes] = $badges[$user->status] ?? [$user->status, 'bg-zinc-100 text-zinc-600 border-zinc-200']; @endphp
              <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $classes }}">{{ $label }}</span>
            </td>
            <td class="px-4 py-3 text-zinc-700">{{ $user->passkeys_count }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-2">
                @if($user->status === \App\Models\User::STATUS_ACTIVE)
                  <form method="POST" action="{{ route('admin.users.reset', $user) }}" onsubmit="return confirm('Revoke all passkeys for {{ $user->username }}? They will re-enroll with their PIN.');">
                    @csrf
                    <button class="inline-flex items-center gap-1.5 rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-700 shadow-sm transition hover:bg-amber-50 focus:ring-2 focus:ring-amber-200 focus:outline-none" type="submit">
                      <x-lucide-key-round class="size-3.5" /> Reset access
                    </button>
                  </form>
                @endif
                @unless($user->is(auth()->user()))
                  <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete {{ $user->username }}? This cannot be undone.');">
                    @csrf @method('DELETE')
                    <button class="inline-flex items-center gap-1.5 rounded-md border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50 focus:ring-2 focus:ring-red-200 focus:outline-none" type="submit">
                      <x-lucide-trash-2 class="size-3.5" /> Delete
                    </button>
                  </form>
                @endunless
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <p class="mt-4 max-w-2xl text-xs leading-5 text-zinc-500">Passkey is mandatory to sign in. A PIN only lets a new or reset user enroll their first passkey — it never signs an active account into the admin area. Use <span class="font-semibold text-amber-700">Reset access</span> if someone loses their device.</p>
@endsection
