<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Formalizes the Guardian/Student link that was previously matched
     * purely by the guardian_email string value: adds a real guardian_id
     * FK on students, and a display name on guardians (needed so the
     * "Existing Guardian" picker on the Student Create form has something
     * to show besides a bare email). guardian_name/guardian_email stay on
     * students exactly as before — every read path in the Guardian guard
     * (DashboardController, ProfileController, StudentController) keeps
     * matching by guardian_email unchanged; guardian_id is additive.
     *
     * The backfill below creates one guardians row per distinct historical
     * guardian_email already used on students (case-insensitively, so
     * "A@x.com" and "a@x.com" collapse to one account) and links every
     * matching student to it, so the picker finds every guardian already
     * in the system, not just ones created going forward.
     */
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->string('name')->nullable()->after('email');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('guardian_id')->nullable()->after('guardian_email')
                ->constrained('guardians')->nullOnDelete();
        });

        $rows = DB::table('students')
            ->select('id', 'guardian_email', 'guardian_name')
            ->whereNotNull('guardian_email')
            ->where('guardian_email', '!=', '')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn ($row) => strtolower(trim($row->guardian_email)));

        foreach ($rows as $email => $studentRows) {
            if ($email === '') {
                continue;
            }

            $name = $studentRows->first(fn ($row) => filled($row->guardian_name))?->guardian_name;

            $guardianId = DB::table('guardians')->where('email', $email)->value('id');

            if (! $guardianId) {
                $guardianId = DB::table('guardians')->insertGetId([
                    'email' => $email,
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($name) {
                DB::table('guardians')->where('id', $guardianId)->whereNull('name')->update(['name' => $name]);
            }

            DB::table('students')
                ->whereRaw('LOWER(TRIM(guardian_email)) = ?', [$email])
                ->update(['guardian_id' => $guardianId]);
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guardian_id');
        });

        Schema::table('guardians', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
