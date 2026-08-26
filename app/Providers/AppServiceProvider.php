<?php

namespace App\Providers;

use App\Models\AcademicSession;
use App\Models\Setting;
use App\Services\AcademicSessionResolver;
use App\Services\SingleSessionService;
use Illuminate\Auth\Events\Login;
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
