<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Services\ExamAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamApiController extends Controller
{
    public function state(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $student = $request->user('student');
        abort_if($attempt->student_id !== $student->id, 403);

        if ($attempt->status === 'in_progress' && $attempt->expires_at->isPast()) {
            app(ExamAttemptService::class)->submit($attempt, $student);
            $attempt->refresh();
        }

        $exam = $attempt->exam()->with('questions.options')->firstOrFail();
        $answers = $attempt->answers()->get()->keyBy('question_id');
        $questions = $exam->questions;

        if ($exam->randomize_questions) {
            $questions = $questions->sortBy(fn ($question) => md5($attempt->id.'-'.$question->id))->values();
        }

        return response()->json([
            'attempt' => [
                'id' => $attempt->id,
                'status' => $attempt->status,
                'started_at' => $attempt->started_at?->toIso8601String(),
                'expires_at' => $attempt->expires_at?->toIso8601String(),
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'result_url' => $attempt->status === 'submitted' ? route('student.results.show', $attempt) : null,
            ],
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'duration_minutes' => $exam->duration_minutes,
                'total_marks' => $exam->total_marks,
                'passing_marks' => $exam->passing_marks,
            ],
            'questions' => $questions->map(function ($question) use ($exam, $attempt, $answers) {
                $options = $question->options;
                if ($exam->randomize_answers) {
                    $options = $options->sortBy(fn ($option) => md5($attempt->id.'-'.$question->id.'-'.$option->id))->values();
                }

                return [
                    'id' => $question->id,
                    'text' => $question->question_text,
                    'marks' => $question->marks,
                    'selected_option_id' => $answers->get($question->id)?->question_option_id,
                    'options' => $options->map(fn ($option) => [
                        'id' => $option->id,
                        'text' => $option->option_text,
                    ])->values(),
                ];
            })->values(),
        ]);
    }

    public function answer(Request $request, ExamAttempt $attempt, ExamAttemptService $service): JsonResponse
    {
        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'option_id' => ['nullable', 'integer'],
        ]);

        $answer = $service->saveAnswer(
            $attempt,
            $request->user('student'),
            (int) $validated['question_id'],
            $validated['option_id'] ? (int) $validated['option_id'] : null
        );

        return response()->json([
            'saved' => true,
            'question_id' => $answer->question_id,
            'selected_option_id' => $answer->question_option_id,
        ]);
    }

    public function submit(Request $request, ExamAttempt $attempt, ExamAttemptService $service): JsonResponse
    {
        $attempt = $service->submit($attempt, $request->user('student'));

        return response()->json([
            'submitted' => true,
            'result_url' => route('student.results.show', $attempt),
        ]);
    }
}
