<?php

namespace App\Http\Middleware;

use App\Services\SingleSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleSession
{
    private const MESSAGE = 'Your account was logged in from another device, so this session has been logged out.';

    /**
     * Handle an incoming request.
     *
     * Enforces a single active session for Student and Branch accounts.
     * When a newer login (from any device) has replaced this session's
     * token, the current session is logged out immediately. Super Admin
     * users are intentionally exempt.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('student')->check()) {
            $response = $this->enforce($request, 'student', Auth::guard('student')->user());

            if ($response) {
                return $response;
            }
        }

        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();

            if ($user->role === 'Branch') {
                $response = $this->enforce($request, 'web', $user);

                if ($response) {
                    return $response;
                }
            }
        }

        return $next($request);
    }

    private function enforce(Request $request, string $guard, $user): ?Response
    {
        $sessionToken = $request->session()->get(SingleSessionService::sessionKey($guard));

        // A session that never recorded a token (e.g. programmatic auth in
        // tests/tinker) is not something this feature has ever established,
        // so it is left alone rather than treated as a stale device.
        if ($sessionToken === null) {
            return null;
        }

        if ($sessionToken === $user->current_session_id) {
            return null;
        }

        Auth::guard($guard)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => self::MESSAGE], 401);
        }

        return redirect()->route('login')->with('login_error', self::MESSAGE);
    }
}
