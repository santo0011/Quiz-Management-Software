<?php

use App\Models\Student;
use App\Services\ZohoStudentService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * One-time backfill: point every Student that already has a stored Zoho
     * payload at the Branch their Enrolment Location maps to (same rule as
     * ZohoStudentService::syncStudentFromZoho(), which now does this on
     * every login). Students whose Location matches no Branch are left as
     * they are.
     */
    public function up(): void
    {
        $zoho = app(ZohoStudentService::class);

        Student::whereNotNull('zoho_payload')->chunkById(200, function ($students) use ($zoho): void {
            foreach ($students as $student) {
                if (! is_array($student->zoho_payload)) {
                    continue;
                }

                $branch = $zoho->resolveBranchFromLocation($zoho->extractLocation($student->zoho_payload));

                if ($branch && (int) $student->branch_id !== (int) $branch->id) {
                    $student->forceFill(['branch_id' => $branch->id])->saveQuietly();
                }
            }
        });
    }

    public function down(): void
    {
        // Data backfill only; previous branch assignments aren't recorded.
    }
};
