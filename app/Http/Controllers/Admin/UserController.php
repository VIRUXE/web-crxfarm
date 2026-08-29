<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount('passkeys')
            ->orderByDesc('is_admin')
            ->orderBy('username')
            ->get();

        return view('admin.users.index', ['users' => $users]);
    }

    /**
     * Recovery lever for a lost or compromised device. Revokes every passkey
     * and drops the account back to `pin_set`, so the user must re-enroll a
     * fresh passkey (bootstrapped with their existing PIN) before they can
     * reach the admin area again. Since passkey is mandatory at login, this
     * is the only way back in for someone who no longer has their authenticator.
     */
    public function resetAccess(Request $request, User $user)
    {
        if (! $user->pin_hash) {
            return back()->withErrors(['user' => "{$user->username} has no PIN set, so they'd have no way back in. Re-invite them instead."]);
        }

        $user->passkeys()->delete();
        $user->forceFill([
            'status' => User::STATUS_PIN_SET,
            'passkey_enrolled_at' => null,
        ])->save();

        return back()->with('status', "Passkeys revoked for {$user->username} — they'll re-enroll with their PIN next time they sign in.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => "You can't delete your own account."]);
        }

        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->withErrors(['user' => "Can't delete the last admin account."]);
        }

        $username = $user->username;
        $user->passkeys()->delete();
        $user->delete();

        return back()->with('status', "Deleted {$username}.");
    }
}
