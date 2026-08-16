<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'avsbera.gpt1@gmail.com',
        ], [
            'name' => 'Super Admin',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);
    }
}
