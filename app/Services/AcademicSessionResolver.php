<?php

namespace App\Services;

use App\Models\AcademicSession;
use Illuminate\Http\Request;

/**
 * Resolves the currently-selected Academic Session for the acting
 * Super Admin, Branch, or Teacher user. Each role keeps its own independent
 * selection, stored in that user's own HTTP session (mirrors how
 * "admin_selected_branch_id" is stored, but namespaced per role so
 * Super Admin, Branch, and Teacher never share one selection).
 */
class AcademicSessionResolver
{
    public static function sessionKey(?string $role): string
    {
        return match ($role) {
            'Branch' => 'branch_selected_academic_session_id',
            'Teacher' => 'teacher_selected_academic_session_id',
            default => 'admin_selected_academic_session_id',
        };
    }

    /**
     * The session key for whichever account is currently authenticated,
     * checked across the web (Super Admin/Branch) and teacher guards since
     * $request->user() alone only resolves the default (web) guard.
     */
    public static function currentRoleKey(Request $request): string
    {
        if ($request->user('teacher')) {
            return self::sessionKey('Teacher');
        }

        return self::sessionKey($request->user()?->role);
    }

    public static function selectedId(Request $request): ?int
    {
        $key = self::currentRoleKey($request);
        $id = $request->session()->get($key);

        if (! $id) {
            return null;
        }

        return AcademicSession::whereKey($id)->exists() ? (int) $id : null;
    }

    public static function selected(Request $request): ?AcademicSession
    {
        $id = self::selectedId($request);

        return $id ? AcademicSession::find($id) : null;
    }

    /**
     * The Session whose date range covers today, i.e. the one that should
     * be treated as "current". When more than one Session's dates overlap
     * today, an active Session is preferred over a closed one; ties are
     * broken by the most recently started, then most recently created.
     */
    public static function currentByDate(): ?AcademicSession
    {
        $today = now()->toDateString();

        return AcademicSession::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Called right after a Super Admin/Branch/Teacher login completes: picks
     * the Session covering today's date for that role's own selection slot.
     * When no Session covers today, any previous selection is cleared so the
     * existing "select an Academic Session" empty-state prompts the user
     * instead of silently continuing to show stale data.
     */
    public static function autoSelectOnLogin(Request $request, string $role): void
    {
        $key = self::sessionKey($role);
        $current = self::currentByDate();

        if ($current) {
            $request->session()->put($key, $current->id);
        } else {
            $request->session()->forget($key);
        }
    }
}
