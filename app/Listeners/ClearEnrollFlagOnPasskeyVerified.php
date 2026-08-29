<?php

namespace App\Listeners;

use Laravel\Passkeys\Events\PasskeyVerified;

/**
 * A successful passkey assertion proves this device already has a working
 * passkey, so any stale "must enroll a passkey" flag (e.g. left over from a
 * PIN attempt earlier in the same session) is cleared — the passkey sign-in
 * itself is enough to reach the admin area.
 */
class ClearEnrollFlagOnPasskeyVerified
{
    public function handle(PasskeyVerified $event): void
    {
        session()->forget('must_enroll_passkey');
    }
}
