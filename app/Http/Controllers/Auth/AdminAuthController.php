<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    /**
     * Passkey-primary login screen. The passkey ceremony itself talks
     * directly to laravel/passkeys' own routes via JS
     * (public/js/passkey-onboarding.js); this view also offers a
     * "use your PIN instead" fallback form posting to pinLogin().
     */
    public function create()
    {
        return view('admin.login');
    }

    /**
     * PIN fallback login. Throttled hard (see routes/web.php,
     * throttle:5,15) since a 6-digit PIN has far less entropy than a
     * passkey. A PIN alone never grants access to the admin area: a user
     * who has set a PIN but not yet enrolled a passkey (status = pin_set)
     * lands right back on the mandatory enroll screen instead — the
     * 'active' middleware enforces that on every /admin/* request, so
     * there's no path from "knows the PIN" to "reaches the catalog admin"
     * without a passkey existing.
     */
    public function pinLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'pin' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->pin_hash || ! Hash::check($credentials['pin'], $user->pin_hash)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid email or PIN.',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return $user->isActive()
            ? redirect()->route('admin.listings.index')
            : redirect()->route('onboarding.passkey.create');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('catalog.index');
    }
}
