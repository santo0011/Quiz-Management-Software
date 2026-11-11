<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Wipes all exam-related data (exams and everything hanging off them, plus
 * question categories) while leaving users, branches, students, teachers,
 * guardians, classes, subjects, settings and logs untouched.
 */
class ExamDataResetService
{
    /**
     * Child tables first so foreign keys are never violated.
     */
    public const TABLES = [
        'exam_answers',
        'exam_attempts',
        'question_options',
        'questions',
        'passage_groups',
        'exam_school_class',
        'exams',
        'question_categories',
    ];

    /**
     * @return array<string, int> rows deleted per table
     */
    public function clear(): array
    {
        $files = array_merge(
            DB::table('passage_groups')->whereNotNull('image_path')->pluck('image_path')->all(),
            DB::table('exam_attempts')->whereNotNull('result_pdf_path')->pluck('result_pdf_path')->all(),
            DB::table('exam_attempts')->whereNotNull('branch_result_pdf_path')->pluck('branch_result_pdf_path')->all(),
        );

        $counts = DB::transaction(function () {
            $counts = [];

            foreach (self::TABLES as $table) {
                $counts[$table] = DB::table($table)->delete();
            }

            return $counts;
        });

        $files = array_values(array_filter(array_unique($files)));

        if ($files) {
            Storage::disk('public')->delete($files);
        }

        return $counts;
    }
}
