<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PasskeyEnrollController extends Controller
{
    /**
     * Mandatory passkey enrollment screen. Reached right after PIN setup
     * (status = pin_set) via an authenticated-but-not-active session; the
     * 'active' middleware bounces anyone here who isn't mid-onboarding.
     * The actual WebAuthn ceremony is handled client-side against
     * laravel/passkeys' own /user/passkeys/options + /user/passkeys routes
     * (see public/js/passkey-onboarding.js) — a successful registration
     * fires PasskeyRegistered, which App\Listeners\ActivateUserOnPasskeyRegistered
     * flips to status = active.
     */
    public function create(Request $request)
    {
        if ($request->user()?->isActive()) {
            return redirect()->route('admin.listings.index');
        }

        return view('auth.passkey-enroll');
    }
}
