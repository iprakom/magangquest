<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Check if user exists by google_id
            $user = User::where('google_id', $googleUser->id)->first();

            if ($user) {
                // User exists, log them in
                Auth::login($user);
                $request->session()->regenerate();

                return $this->authenticated($request, $user);
            }

            // Check if user exists by email
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // Link google_id to existing user
                $user->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                ]);

                Auth::login($user);
                $request->session()->regenerate();

                return $this->authenticated($request, $user);
            }

            // Create new user
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'avatar' => $googleUser->avatar,
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'role' => User::ROLE_PLAYER,
                'onboarding_status' => User::ONBOARDING_PENDING,
            ]);

            Auth::login($user);
            $request->session()->regenerate();

            return $this->authenticated($request, $user);

        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Google authentication failed. Please try again.');
        }
    }

    /**
     * Handle authenticated user redirect
     */
    protected function authenticated(Request $request, User $user)
    {
        // Check onboarding status and redirect accordingly
        if ($user->onboarding_status === User::ONBOARDING_PENDING) {
            return redirect()->intended('/onboarding');
        }

        return redirect()->intended('/dashboard');
    }
}
