<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamAttemptService
{
    public function start(Exam $exam, Student $student): ExamAttempt
    {
        $this->ensureExamAvailableForStudent($exam, $student);

        $inProgress = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($inProgress) {
            return $inProgress;
        }

        $this->ensureStudentHasAttemptsRemaining($exam, $student);
        $this->ensureExamHasQuestions($exam);

        return DB::transaction(function () use ($exam, $student): ExamAttempt {
            $attemptNumber = ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->count() + 1;

            if ($attemptNumber > $exam->maximum_attempts) {
                throw ValidationException::withMessages([
                    'exam' => 'You have reached the maximum attempts for this exam.',
                ]);
            }

            $expiresAt = now()->addMinutes($exam->duration_minutes);
            if ($exam->ends_at && $exam->ends_at->lt($expiresAt)) {
                $expiresAt = $exam->ends_at;
            }

            return ExamAttempt::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'branch_id' => $student->branch_id,
                'school_class_id' => $student->class_id,
                'attempt_number' => $attemptNumber,
                'started_at' => now(),
                'expires_at' => $expiresAt,
            ]);
        });
    }

    public function saveAnswer(ExamAttempt $attempt, Student $student, int $questionId, ?int $optionId): ExamAnswer
    {
        $this->authorizeAttempt($attempt, $student);
        $this->ensureAttemptOpen($attempt);

        $question = Question::where('exam_id', $attempt->exam_id)->whereKey($questionId)->firstOrFail();

        $option = null;
        if ($optionId) {
            $option = QuestionOption::where('question_id', $question->id)->whereKey($optionId)->firstOrFail();
        }

        return ExamAnswer::updateOrCreate([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ], [
            'question_option_id' => $option?->id,
        ]);
    }

    public function submit(ExamAttempt $attempt, Student $student): ExamAttempt
    {
        $this->authorizeAttempt($attempt, $student);

        if ($attempt->status === 'submitted') {
            return $attempt;
        }

        return DB::transaction(function () use ($attempt): ExamAttempt {
            $attempt->refresh();
            if ($attempt->status === 'submitted') {
                return $attempt;
            }

            $exam = $attempt->exam()->with('questions.options')->firstOrFail();
            $answersByQuestion = $attempt->answers()->get()->keyBy('question_id');
            $correct = 0;
            $wrong = 0;
            $unanswered = 0;
            $obtained = 0.0;

            foreach ($exam->questions as $question) {
                $answer = $answersByQuestion->get($question->id) ?: ExamAnswer::create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'question_option_id' => null,
                ]);

                $selected = $answer->question_option_id
                    ? $question->options->firstWhere('id', $answer->question_option_id)
                    : null;

                if (! $selected) {
                    $unanswered++;
                    $marksAwarded = 0;
                    $isCorrect = false;
                } elseif ($selected->is_correct) {
                    $correct++;
                    $marksAwarded = (float) $question->marks;
                    $isCorrect = true;
                } else {
                    $wrong++;
                    $marksAwarded = $exam->negative_marking_enabled ? -1 * (float) $exam->negative_marks : 0;
                    $isCorrect = false;
                }

                $obtained += $marksAwarded;
                $answer->update([
                    'is_correct' => $isCorrect,
                    'marks_awarded' => $marksAwarded,
                ]);
            }

            $obtained = max(0, $obtained);
            $percentage = $exam->total_marks > 0 ? round(($obtained / $exam->total_marks) * 100, 2) : 0;
            $passingMarks = $exam->passing_marks ?? 0;

            $attempt->update([
                'submitted_at' => now(),
                'obtained_marks' => $obtained,
                'percentage' => $percentage,
                'correct_count' => $correct,
                'wrong_count' => $wrong,
                'unanswered_count' => $unanswered,
                'is_passed' => $obtained >= $passingMarks,
                'status' => 'submitted',
            ]);

            return $attempt->refresh();
        });
    }

    public function ensureStudentCanAttempt(Exam $exam, Student $student): void
    {
        $this->ensureExamAvailableForStudent($exam, $student);
        $this->ensureStudentHasAttemptsRemaining($exam, $student);
        $this->ensureExamHasQuestions($exam);
    }

    private function ensureExamAvailableForStudent(Exam $exam, Student $student): void
    {
        if (! $student->isActive()) {
            throw ValidationException::withMessages(['exam' => 'Your student account has been deactivated. Please contact your administrator.']);
        }

        if ($student->branch && ! $student->branch->isActive()) {
            throw ValidationException::withMessages(['exam' => 'Your branch has been deactivated. Please contact your administrator.']);
        }

        if ($exam->branch_id !== $student->branch_id || $exam->school_class_id !== $student->class_id) {
            throw ValidationException::withMessages(['exam' => 'This exam is not assigned to your class.']);
        }

        if (! $exam->isOpen()) {
            throw ValidationException::withMessages(['exam' => 'This exam is not currently available.']);
        }
    }

    private function ensureStudentHasAttemptsRemaining(Exam $exam, Student $student): void
    {
        if ($exam->remainingAttemptsFor($student) <= 0) {
            throw ValidationException::withMessages(['exam' => 'You have reached the maximum attempts for this exam.']);
        }
    }

    private function ensureExamHasQuestions(Exam $exam): void
    {
        if ($exam->questions()->count() === 0) {
            throw ValidationException::withMessages(['exam' => 'This exam has no questions yet.']);
        }
    }

    private function authorizeAttempt(ExamAttempt $attempt, Student $student): void
    {
        abort_if($attempt->student_id !== $student->id, 403, 'This attempt does not belong to you.');
    }

    private function ensureAttemptOpen(ExamAttempt $attempt): void
    {
        if ($attempt->status !== 'in_progress') {
            throw ValidationException::withMessages(['attempt' => 'This attempt has already been submitted.']);
        }

        if ($attempt->expires_at->isPast()) {
            throw ValidationException::withMessages(['attempt' => 'This attempt has expired. Please submit to view your result.']);
        }
    }
}
