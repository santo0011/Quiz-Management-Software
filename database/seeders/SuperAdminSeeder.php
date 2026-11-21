<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create the superadmin@yopmail.com Super Admin account.
     *
     * Uses firstOrCreate so re-running never resets the password of an
     * existing account.
     *
     * Run alone: php artisan db:seed --class=SuperAdminSeeder
     */
    public function run(): void
    {
        User::firstOrCreate([
            'email' => 'superadmin@yopmail.com',
        ], [
            'name' => 'Super Admin',
            'role' => 'Super Admin',
            'password' => Hash::make('12345678'),
        ]);
    }
}
