<?php

use App\Http\Middleware\EnsureAcademicSessionSelected;
use App\Http\Middleware\EnsureSingleSession;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Support\RoleRedirector;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Safety net for exam attempts whose time ran out while the
        // student was away and never touched the exam runner again to
        // trigger its own lazy expiry check.
        $schedule->command('exams:submit-expired')->everyMinute();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'active' => EnsureUserIsActive::class,
            'single_session' => EnsureSingleSession::class,
            'require_academic_session' => EnsureAcademicSessionSelected::class,
        ]);

        // An already-authenticated visitor hitting a guest-only page (e.g.
        // /login) is sent to their own dashboard instead of Laravel's
        // default fallback, which would 404/redirect to "/" since this app
        // has no route named "dashboard" or "home".
        RedirectIfAuthenticated::redirectUsing(function () {
            $user = RoleRedirector::currentUser();

            return $user ? RoleRedirector::dashboardUrl($user) : route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Redirect unauthenticated users to the login page with a friendly message.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('login'))->with('login_error', 'Please login to continue.');
        });

        // For 401 Unauthorized HTTP exceptions, redirect to login with a clean message.
        $exceptions->render(function (UnauthorizedHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized.'], 401);
            }

            return redirect()->guest(route('login'))->with('login_error', 'Please login to continue.');
        });

        // For 404s, redirect non-API users to a clean custom 404 page.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Route not found.'], 404);
            }

            return response()->view('errors.404', [], 404);
        });
    })->create();