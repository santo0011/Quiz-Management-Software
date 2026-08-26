<?php

namespace App\Http\Middleware;

use App\Services\AcademicSessionResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks creating Session-dependent data (Students, Exams, ...) until the
 * acting user has picked an Academic Session from the navbar. Redirects
 * back to the given index route, whose own empty-state ("Select an
 * Academic Session to continue") is the professional prompt — this
 * middleware deliberately does not flash an error message.
 */
class EnsureAcademicSessionSelected
{
    public function handle(Request $request, Closure $next, string $redirectRouteName): Response
    {
        if (AcademicSessionResolver::selectedId($request) === null) {
            return redirect()->route($redirectRouteName);
        }

        return $next($request);
    }
}
