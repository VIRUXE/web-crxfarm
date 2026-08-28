<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seeds the local-dev admin directly into `pin_set` (PIN already usable
     * as a fallback), NOT `active` — a seeder can't complete a real WebAuthn
     * ceremony, so passkey enrollment still has to happen once through the
     * actual browser flow at /onboarding/passkey after logging in with this
     * PIN via the "use your PIN instead" link on /admin/login. Until that's
     * done, login is still blocked (status != active), matching production
     * behavior — this just skips having to email/copy a magic link locally.
     */
    public function run(): void
    {
        $pin = env('ADMIN_SEED_PIN', '123456');

        User::updateOrCreate(
            ['email' => 'jeremiah@crxfarm.local'],
            [
                'name' => 'Jeremiah',
                'is_admin' => true,
                'pin_hash' => Hash::make($pin),
                'pin_set_at' => now(),
                'status' => User::STATUS_PIN_SET,
            ]
        );
    }
}
