<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Super Admin must choose which Branch they're working in (via
 * BranchSelectionController) before touching branch-owned data — Students,
 * Exams, Questions, Passage Groups, and Results. Without this, any route
 * behind it redirects back to the branch-selection screen.
 */
class EnsureAdminBranchSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('admin_selected_branch_id')) {
            return redirect()->route('admin.branch-selection.index')
                ->with('success', 'Please select a branch first to manage branch-related data.');
        }

        return $next($request);
    }
}
