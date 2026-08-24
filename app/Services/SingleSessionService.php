<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Enforces "one active session per account" for the student and branch
 * guards. A random token is stored both on the user's own database row and
 * in the current PHP session; a request is only treated as the account's
 * active session when the two match. Logging in again (including a silent
 * "remember me" re-login) always wins and invalidates whatever session held
 * the token before it.
 */
class SingleSessionService
{
    public static function sessionKey(string $guard): string
    {
        return "single_session_token_{$guard}";
    }

    /**
     * Mark the current session as the account's one active session,
     * overwriting whatever session was previously active for this user.
     */
    public static function establish(Authenticatable $user, string $guard): void
    {
        $token = Str::random(60);

        $user->forceFill(['current_session_id' => $token])->save();

        Session::put(self::sessionKey($guard), $token);
    }
}
