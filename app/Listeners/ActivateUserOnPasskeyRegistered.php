<?php

namespace App\Listeners;

use App\Models\User;
use Laravel\Passkeys\Events\PasskeyRegistered;

/**
 * Completes onboarding (invited -> pin_set -> active) the moment a user
 * finishes the mandatory passkey enrollment step. Until this fires, the
 * user cannot log in at all - not via passkey (they have none yet) and
 * not via PIN (blocked server-side while status != active).
 */
class ActivateUserOnPasskeyRegistered
{
    public function handle(PasskeyRegistered $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            $user->forceFill([
                'passkey_enrolled_at' => now(),
                'status' => User::STATUS_ACTIVE,
            ])->save();
        }

        // A passkey now exists on this device (first one, or an added device),
        // so this session has satisfied the enrollment gate — let it into the
        // admin area.
        session()->forget('must_enroll_passkey');
    }
}
