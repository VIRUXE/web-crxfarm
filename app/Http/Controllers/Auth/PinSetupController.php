<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PinSetupController extends Controller
{
    /**
     * Show the PIN-setup form. Reached only via a signed magic link,
     * so no separate auth is required to view it.
     */
    public function create(Request $request, User $user)
    {
        if ($user->status !== User::STATUS_INVITED) {
            // Already set a PIN (or fully active) - this link has done its job.
            return redirect()
                ->route('admin.login')
                ->with('status', 'This invite link has already been used.');
        }

        return view('auth.pin-setup', ['user' => $user]);
    }

    public function store(Request $request, User $user)
    {
        if ($user->status !== User::STATUS_INVITED) {
            return redirect()->route('admin.login');
        }

        $data = $request->validate([
            'pin' => ['required', 'digits:6', 'confirmed'],
        ]);

        // Login is PIN-only now (no username collected at sign-in) - the
        // PIN itself is how a user gets identified, so two people can't
        // share one. PINs are hashed, so this has to be a linear check
        // against existing hashes rather than a WHERE clause; fine at the
        // handful-of-staff scale this app operates at.
        $collision = User::whereNotNull('pin_hash')
            ->where('id', '!=', $user->id)
            ->get()
            ->contains(fn (User $other) => Hash::check($data['pin'], $other->pin_hash));

        if ($collision) {
            throw ValidationException::withMessages([
                'pin' => 'That PIN is already in use by another account - pick a different one.',
            ]);
        }

        $user->forceFill([
            'pin_hash' => Hash::make($data['pin']),
            'pin_set_at' => now(),
            'status' => User::STATUS_PIN_SET,
        ])->save();

        // Log the user in now - the passkey registration endpoint
        // (laravel/passkeys) requires an authenticated session, and the
        // 'active' middleware keeps this session confined to the enroll
        // page until a passkey actually exists.
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('onboarding.passkey.create');
    }
}
