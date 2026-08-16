<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;

class SuperAdminServiceProvider extends ServiceProvider
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
     *
     * Automatically ensures a Super Admin account exists on every application startup.
     */
    public function boot(): void
    {
        try {
            User::updateOrCreate(
                ['email' => 'avsbera.gpt@gmail.com'],
                [
                    'name' => 'Super Admin',
                    'role' => 'Super Admin',
                    'password' => Hash::make('123456'),
                ]
            );
        } catch (\Throwable $e) {
            // Database may not be migrated yet (e.g., during `php artisan migrate`).
            // Silently skip - the seeder will handle it, or the next request will create it.
        }
    }
}