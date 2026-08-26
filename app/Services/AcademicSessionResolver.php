<?php

namespace App\Services;

use App\Models\AcademicSession;
use Illuminate\Http\Request;

/**
 * Resolves the currently-selected Academic Session for the acting
 * Super Admin or Branch user. Each role keeps its own independent
 * selection, stored in that user's own HTTP session (mirrors how
 * "admin_selected_branch_id" is stored, but namespaced per role so
 * Super Admin and Branch never share one selection).
 */
class AcademicSessionResolver
{
    public static function sessionKey(?string $role): string
    {
        return $role === 'Branch' ? 'branch_selected_academic_session_id' : 'admin_selected_academic_session_id';
    }

    public static function selectedId(Request $request): ?int
    {
        $key = self::sessionKey($request->user()?->role);
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
}
