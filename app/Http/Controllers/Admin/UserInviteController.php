<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserInviteController extends Controller
{
    public function create()
    {
        return view('admin.users.invite');
    }

    /**
     * Create the account with a randomly generated 6-digit PIN and show that
     * PIN to the admin once, on screen, to hand to the new user out of band
     * (in person, text, whatever) — this app has no email or any other
     * delivery channel. The user is created straight into `pin_set`: they
     * sign in with the PIN, which drops them on mandatory passkey enrollment
     * (see the PIN login flow), and enrolling a passkey activates the account.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'alpha_dash', 'max:255', 'unique:users,username'],
        ]);

        $pin = $this->generateUniquePin();

        $user = User::create([
            'name' => $data['username'],
            'username' => $data['username'],
            'is_admin' => true,
            'status' => User::STATUS_PIN_SET,
            'pin_hash' => Hash::make($pin),
            'pin_set_at' => now(),
        ]);

        return redirect()
            ->route('admin.users.invite.create')
            ->with('invited_username', $user->username)
            ->with('invited_pin', $pin);
    }

    /**
     * A PIN identifies a user at login (it's the only credential collected on
     * the PIN fallback form), so it must be unique across accounts. PINs are
     * hashed, so uniqueness is a linear check against existing hashes — fine
     * at the handful-of-staff scale this app runs at.
     */
    private function generateUniquePin(): string
    {
        do {
            $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $taken = User::whereNotNull('pin_hash')
                ->get()
                ->contains(fn (User $other) => Hash::check($pin, $other->pin_hash));
        } while ($taken);

        return $pin;
    }
}
