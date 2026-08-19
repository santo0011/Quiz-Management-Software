<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * Redirects authenticated users with the wrong role to their own dashboard.
     * Redirects unauthenticated visitors to the login page.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('login'))->with('login_error', 'Please login to continue.');
        }

        if (! in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You are not allowed to access this area.'], 403);
            }

            // Redirect authenticated users to their own dashboard.
            if (Auth::guard('student')->check() && $user instanceof \App\Models\Student) {
                return redirect()->route('student.dashboard')
                    ->with('error', 'You do not have permission to access that area.');
            }

            // Web guard users (Super Admin / Branch)
            $dashboardRoute = $user->role === 'Super Admin'
                ? 'admin.dashboard'
                : 'branch.dashboard';

            return redirect()->route($dashboardRoute)
                ->with('error', 'You do not have permission to access that area.');
        }

        return $next($request);
    }
}