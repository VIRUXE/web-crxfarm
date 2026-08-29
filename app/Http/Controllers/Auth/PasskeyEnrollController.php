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
     * (see public/js/passkey-onboarding.js) - a successful registration
     * fires PasskeyRegistered, which App\Listeners\ActivateUserOnPasskeyRegistered
     * flips to status = active.
     */
    public function create(Request $request)
    {
        $mustEnroll = (bool) $request->session()->get('must_enroll_passkey');

        // An active user only lands here to add a passkey to a new device
        // (must_enroll set by a PIN sign-in). Without that flag they already
        // have device access, so send them on to the dashboard.
        if ($request->user()?->isActive() && ! $mustEnroll) {
            return redirect()->route('admin.listings.index');
        }

        return view('auth.passkey-enroll', [
            'isAdditionalDevice' => $request->user()?->isActive() && $mustEnroll,
        ]);
    }
}
