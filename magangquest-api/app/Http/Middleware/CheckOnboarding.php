<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class CheckOnboarding
{
    /**
     * Handle an incoming request.
     *
     * Restricts access if user onboarding_status is not 'active'
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if ($user->onboarding_status !== User::ONBOARDING_ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'Onboarding not completed. Please complete onboarding first.',
                'onboarding_status' => $user->onboarding_status,
                'redirect' => '/onboarding',
            ], 403);
        }

        return $next($request);
    }
}
