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
     * Passkey-primary login screen, living at /admin itself (not a
     * separate /admin/login path) — it doubles as the admin entry point.
     * Not gated by the 'guest' middleware, because an already-authenticated
     * visit to /admin needs to fall through to the right place instead of
     * bouncing to the framework's generic "already logged in" default
     * (which would land on the public catalog homepage, not the dashboard):
     * an active admin goes straight to the listings dashboard, one still
     * mid-onboarding goes back to mandatory passkey enrollment, and only an
     * actual guest sees the login form.
     *
     * The passkey ceremony itself talks directly to laravel/passkeys' own
     * routes via JS (public/js/passkey-onboarding.js); this view also
     * offers a "use your PIN instead" fallback form posting to pinLogin().
     */
    public function create()
    {
        if (Auth::check()) {
            return Auth::user()->isActive()
                ? redirect()->route('admin.listings.index')
                : redirect()->route('onboarding.passkey.create');
        }

        return view('admin.login');
    }

    /**
     * PIN fallback login — PIN alone, no username collected. Since PINs
     * are hashed there's no direct lookup column to query, so this checks
     * the submitted PIN against every user with a PIN set; PinSetupController
     * enforces PIN uniqueness at setup time specifically so this resolves
     * to at most one account. Fine at the handful-of-staff scale this app
     * runs at — this is not meant to scale to a large user base.
     *
     * Throttled hard (see routes/web.php, throttle:5,15) since a 6-digit
     * PIN has far less entropy than a passkey. A PIN alone never grants
     * access to the admin area: a user who has set a PIN but not yet
     * enrolled a passkey (status = pin_set) lands right back on the
     * mandatory enroll screen instead — the 'active' middleware enforces
     * that on every /admin/* request, so there's no path from "knows the
     * PIN" to "reaches the catalog admin" without a passkey existing.
     */
    public function pinLogin(Request $request)
    {
        $credentials = $request->validate([
            'pin' => ['required', 'digits:6'],
        ]);

        $user = User::whereNotNull('pin_hash')
            ->get()
            ->first(fn (User $candidate) => Hash::check($credentials['pin'], $candidate->pin_hash));

        if (! $user) {
            throw ValidationException::withMessages([
                'pin' => 'Invalid PIN.',
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
