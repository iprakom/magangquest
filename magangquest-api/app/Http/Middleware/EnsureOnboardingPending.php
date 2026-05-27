<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingPending
{
    /**
     * Handle an incoming request.
     * Only allows users with ONBOARDING_PENDING status to proceed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->onboarding_status !== User::ONBOARDING_PENDING) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Onboarding already completed.'], 403);
            }

            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
