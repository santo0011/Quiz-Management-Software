<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An Exam can now be assigned to several Grades at once (one Exam row,
 * many Grades — never a duplicated Exam per Grade). This pivot is the
 * source of truth for Student eligibility.
 *
 * exams.school_class_id is kept as the Exam's "primary" Grade (the first
 * one selected) so existing reads of $exam->schoolClass keep working, and
 * every existing Exam is backfilled into the pivot with that Grade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_school_class', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['exam_id', 'school_class_id']);
            $table->index('school_class_id');
        });

        $now = now();

        DB::table('exams')
            ->whereNotNull('school_class_id')
            ->orderBy('id')
            ->select(['id', 'school_class_id'])
            ->chunk(500, function ($exams) use ($now): void {
                DB::table('exam_school_class')->insertOrIgnore(
                    $exams->map(fn ($exam) => [
                        'exam_id' => $exam->id,
                        'school_class_id' => $exam->school_class_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_school_class');
    }
};
