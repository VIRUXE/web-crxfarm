<?php

namespace App\Providers;

use App\Listeners\ActivateUserOnPasskeyRegistered;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Events\PasskeyRegistered;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(PasskeyRegistered::class, ActivateUserOnPasskeyRegistered::class);

        // Defense in depth: even though only users with a stored passkey can
        // reach a valid assertion, a user mid-onboarding (invited/pin_set)
        // should never be able to complete a passkey login.
        Passkeys::authorizeLoginUsing(function (Request $request, PasskeyUser $user, Passkey $passkey): bool {
            if ($user instanceof User && ! $user->isActive()) {
                throw ValidationException::withMessages([
                    'credential' => ['Finish setting up your account before signing in.'],
                ]);
            }

            return true;
        });
    }
}
