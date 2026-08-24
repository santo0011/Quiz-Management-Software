<?php

namespace App\Support;

use App\Models\Student;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

/**
 * Single source of truth for "which dashboard does this account belong to".
 * Used by the guest middleware (an already-authenticated visitor hitting
 * /login), the role middleware (a wrong-role access attempt), and the error
 * pages (the contextual "Go to Dashboard" button) so the role-to-route
 * mapping only has to live in one place.
 */
class RoleRedirector
{
    public static function dashboardRouteName(Authenticatable $user): string
    {
        if ($user instanceof Student) {
            return 'student.dashboard';
        }

        return $user->role === 'Super Admin' ? 'admin.dashboard' : 'branch.dashboard';
    }

    public static function dashboardUrl(Authenticatable $user): string
    {
        return route(self::dashboardRouteName($user));
    }

    /**
     * The currently authenticated account, checked across both guards
     * (web, student), or null when nobody is logged in.
     */
    public static function currentUser(): ?Authenticatable
    {
        if (Auth::guard('web')->check()) {
            return Auth::guard('web')->user();
        }

        if (Auth::guard('student')->check()) {
            return Auth::guard('student')->user();
        }

        return null;
    }
}
