<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Branch accounts now log in with a unique User ID (users.username) instead
 * of their email, so one email address can be shared by several branches.
 * The unique constraints on users.email and branches.email are relaxed to
 * plain indexes; Super Admin emails stay unique at the application level.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('name');
        });

        // Give every existing Branch login a User ID derived from its email
        // (the part before @), made unique with a numeric suffix if needed.
        $taken = [];

        DB::table('users')->where('role', 'Branch')->orderBy('id')->get(['id', 'email', 'name'])
            ->each(function ($user) use (&$taken) {
                $base = Str::lower(Str::slug(Str::before((string) $user->email, '@') ?: (string) $user->name, '_'));
                $base = Str::limit($base !== '' ? $base : 'branch', 40, '');
                $candidate = $base;
                $i = 1;

                while (in_array($candidate, $taken, true)) {
                    $candidate = $base.'_'.(++$i);
                }

                $taken[] = $candidate;
                DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
            $table->index('email');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropUnique('branches_email_unique');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex('branches_email_index');
            $table->unique('email');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_email_index');
            $table->unique('email');
            $table->dropUnique('users_username_unique');
            $table->dropColumn('username');
        });
    }
};
