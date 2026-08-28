<?php

namespace App\Providers;

use App\Models\AcademicSession;
use App\Models\Setting;
use App\Services\AcademicSessionResolver;
use App\Services\SingleSessionService;
use Illuminate\Auth\Events\Login;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.custom');

        $this->applyStoredMailSettings();
        $this->registerSingleSessionOnRememberLogin();
        $this->shareAcademicSessionWithNavbar();
        $this->blockDestructiveArtisanCommandsInProduction();
    }

    /**
     * Hard safety net for live data: these commands drop/rebuild the entire
     * schema (or wipe every table), destroying every Student, Exam,
     * Question, Result, Session, Grade, and Subject in the database. Laravel
     * already asks for interactive confirmation on "production", but a
     * `--force` flag (routine in deploy scripts) silently skips that prompt
     * — this listener refuses to run them at all when APP_ENV=production,
     * with no way to override via a flag. Anything short of these (plain
     * `migrate`, `migrate:rollback`, `db:seed`) is left alone: normal
     * forward migrations only add/alter, never drop existing data (see the
     * migration review in DEPLOYMENT.md), and rollback is sometimes a
     * legitimate way to undo a migration that hasn't shipped yet.
     *
     * Throws rather than calling exit(): a real `php artisan <cmd>`
     * invocation runs through Symfony Console's exception-catching run
     * loop, so this still prints cleanly and exits non-zero — but unlike
     * exit(), it doesn't kill the whole PHP process, so it can't take down
     * an in-process caller (Artisan::call(), or a test run) with it.
     */
    private function blockDestructiveArtisanCommandsInProduction(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            $blocked = ['migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:wipe'];

            if (! $this->app->environment('production') || ! in_array($event->command, $blocked, true)) {
                return;
            }

            throw new \RuntimeException(
                "BLOCKED: \"{$event->command}\" is disabled in production. This command drops or wipes existing tables — ".
                'it would permanently delete every Student, Exam, Question, Result, and other live record. Run it against '.
                'a local/staging database instead. If production data genuinely needs to be reset, do that manually and '.
                'deliberately outside of this application.'
            );
        });
    }

    /**
     * The navbar's Academic Session dropdown is rendered from
     * layouts.admin/layouts.branch, which are included by every Admin/Branch
     * page — sharing the data via a composer avoids threading it through
     * every controller individually.
     */
    private function shareAcademicSessionWithNavbar(): void
    {
        View::composer(['layouts.admin', 'layouts.branch'], function ($view): void {
            if (! Schema::hasTable('academic_sessions') || ! Auth::guard('web')->check()) {
                $view->with(['academicSessions' => collect(), 'selectedAcademicSession' => null]);

                return;
            }

            $view->with([
                'academicSessions' => AcademicSession::orderByDesc('start_date')->get(),
                'selectedAcademicSession' => AcademicSessionResolver::selected(request()),
            ]);
        });
    }

    /**
     * Explicit logins already call SingleSessionService::establish() from
     * LoginController. A "remember me" cookie can also silently re-log a
     * student/branch user in (Illuminate\Auth\SessionGuard fires the same
     * Login event for that), which this listener catches so that scenario
     * still claims the device as the account's one active session.
     */
    private function registerSingleSessionOnRememberLogin(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            if (! in_array($event->guard, ['student', 'web'], true)) {
                return;
            }

            if ($event->guard === 'web' && $event->user->role !== 'Branch') {
                return;
            }

            if (! Auth::guard($event->guard)->viaRemember()) {
                return;
            }

            SingleSessionService::establish($event->user, $event->guard);
        });
    }

    /**
     * Override the .env-sourced mail config with values saved on the
     * Super Admin Settings page, when present. Guarded so artisan
     * commands (e.g. migrate) still work before the table exists.
     */
    private function applyStoredMailSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $settings = Setting::query()->first();
        } catch (\Throwable) {
            return;
        }

        if (! $settings || ! $settings->mail_host) {
            return;
        }

        Config::set('mail.default', $settings->mail_mailer ?: 'smtp');
        Config::set('mail.mailers.smtp.host', $settings->mail_host);
        Config::set('mail.mailers.smtp.port', $settings->mail_port ?: 587);
        Config::set('mail.mailers.smtp.username', $settings->mail_username);
        Config::set('mail.mailers.smtp.password', $settings->mail_password);
        Config::set('mail.mailers.smtp.encryption', $settings->mail_encryption);

        if ($settings->mail_from_address) {
            Config::set('mail.from.address', $settings->mail_from_address);
            Config::set('mail.from.name', $settings->mail_from_name ?: config('app.name'));
        }
    }
}
