<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * Checks if the authenticated user (Branch or Student) is still active.
     * If deactivated, logs them out immediately and redirects to login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check Branch/Admin users (web guard)
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();

            // Super Admin is always active
            if ($user->role === 'Super Admin') {
                return $next($request);
            }

            // Branch user - check branch active status
            if ($user->role === 'Branch') {
                $branch = $user->branch;

                if ($branch && ! $branch->isActive()) {
                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')
                        ->with('login_error', 'Your branch account has been deactivated. Please contact the administrator.');
                }
            }
        }

        // Check Student users (student guard)
        if (Auth::guard('student')->check()) {
            $student = Auth::guard('student')->user();

            if (! $student->isActive()) {
                Auth::guard('student')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('login_error', 'Your student account has been deactivated. Please contact the administrator.');
            }

            // Check if student's branch is active
            if ($student->branch && ! $student->branch->isActive()) {
                Auth::guard('student')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('login_error', 'Your branch has been deactivated. Please contact the administrator.');
            }
        }

        return $next($request);
    }
}