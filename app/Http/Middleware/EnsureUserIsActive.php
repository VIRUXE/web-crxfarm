<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the real admin area. A session can technically be authenticated
 * mid-onboarding (we log the user in right after PIN setup so the passkey
 * registration endpoint, which requires `auth`, is reachable) — this
 * middleware stops that session from reaching anything else until
 * passkey enrollment actually completes.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isActive()) {
            return redirect()->route('onboarding.passkey.create');
        }

        return $next($request);
    }
}
