<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingActive
{
    /**
     * Handle an incoming request.
     * Only allows users with ONBOARDING_ACTIVE status to proceed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->onboarding_status !== User::ONBOARDING_ACTIVE) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Onboarding not completed.'], 403);
            }

            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
