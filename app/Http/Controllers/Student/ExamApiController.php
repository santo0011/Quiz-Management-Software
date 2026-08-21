<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Services\ExamAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        $exam = $attempt->exam;
        $answers = $attempt->answers()->get()->keyBy('question_id');

        $items = $exam->orderedItems();

        if ($exam->randomize_questions) {
            // Shuffle top-level items as units — a passage group's own
            // questions always stay together and in their configured order.
            $items = $items->sortBy(fn (array $item) => md5($attempt->id.'-'.($item['type'] === 'question' ? $item['question']->id : 'group-'.$item['group']->id)))->values();
        }

        $mapQuestion = function ($question) use ($exam, $attempt, $answers) {
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
        };

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
            'items' => $items->map(function (array $item) use ($mapQuestion) {
                if ($item['type'] === 'question') {
                    return [
                        'type' => 'question',
                        'question' => $mapQuestion($item['question']),
                    ];
                }

                return [
                    'type' => 'passage_group',
                    'passage' => [
                        'id' => $item['group']->id,
                        'title' => $item['group']->title,
                        'content' => $item['group']->content,
                        'image_url' => $item['group']->image_path ? Storage::url($item['group']->image_path) : null,
                    ],
                    'questions' => $item['group']->questions->map($mapQuestion)->values(),
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
