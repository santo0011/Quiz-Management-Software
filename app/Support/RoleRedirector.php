<?php

namespace App\Support;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\Teacher;
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

        if ($user instanceof Guardian) {
            return 'guardian.dashboard';
        }

        if ($user instanceof Teacher) {
            return 'teacher.dashboard';
        }

        return $user->role === 'Super Admin' ? 'admin.dashboard' : 'branch.dashboard';
    }

    public static function dashboardUrl(Authenticatable $user): string
    {
        return route(self::dashboardRouteName($user));
    }

    /**
     * The currently authenticated account, checked across all guards
     * (web, student, guardian), or null when nobody is logged in.
     */
    public static function currentUser(): ?Authenticatable
    {
        if (Auth::guard('web')->check()) {
            return Auth::guard('web')->user();
        }

        if (Auth::guard('student')->check()) {
            return Auth::guard('student')->user();
        }

        if (Auth::guard('guardian')->check()) {
            return Auth::guard('guardian')->user();
        }

        if (Auth::guard('teacher')->check()) {
            return Auth::guard('teacher')->user();
        }

        return null;
    }
}
