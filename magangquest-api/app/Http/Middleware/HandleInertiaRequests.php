<?php

namespace App\Http\Middleware;

use Inertia\Middleware as InertiaMiddleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class HandleInertiaRequests extends InertiaMiddleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines if validation errors should be mapped to a single error message per field.
     *
     * @var bool
     */
    protected $withAllErrors = false;

    /**
     * The paths that should be excluded from server-side rendering.
     *
     * @var array<int, string>
     */
    protected $withoutSsr = [];

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            'errors' => Inertia::always($this->resolveValidationErrors($request)),
            'auth' => Auth::user() ? [
                'user' => Auth::user()->only(['id', 'name', 'email', 'role', 'nip', 'room', 'unit_kerja', 'start_date', 'end_date', 'intern_type']),
            ] : null,
        ];
    }

    /**
     * Define the props that are shared once and remembered across navigations.
     *
     * @return array<string, callable|OnceProp>
     */
    public function shareOnce(Request $request): array
    {
        return [];
    }
}
