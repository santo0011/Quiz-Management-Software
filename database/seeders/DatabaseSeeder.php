<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Safe to re-run against a database that already has data: this only
     * ever creates the initial Super Admin account if one doesn't already
     * exist by this email. It intentionally uses firstOrCreate (not
     * updateOrCreate) so re-running this seeder — e.g. `db:seed --force` in
     * a deploy script — can never silently reset a real admin's password
     * back to the placeholder below, and never touches any other table.
     */
    public function run(): void
    {
        User::firstOrCreate([
            'email' => 'avsbera.gpt1@gmail.com',
        ], [
            'name' => 'Super Admin',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);
    }
}
