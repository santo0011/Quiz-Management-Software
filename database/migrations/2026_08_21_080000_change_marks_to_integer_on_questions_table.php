<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('questions')->update(['marks' => DB::raw('ROUND(marks)')]);

        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedInteger('marks')->change();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->decimal('marks', 8, 2)->change();
        });
    }
};
