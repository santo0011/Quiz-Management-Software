<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\MultiQuestionRequest;
use App\Http\Requests\QuestionRequest;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        return view('branch.questions.index', [
            'branch' => $branch,
            'exams' => Exam::withCount('questions')->with('schoolClass')->forBranch($branch->id)->latest()->paginate(10),
        ]);
    }

    public function create(Request $request, Exam $exam): View
    {
        $this->authorizeExam($request, $exam);

        return view('branch.questions.create', [
            'branch' => $request->user()->branch,
            'exam' => $exam->load('schoolClass'),
            'question' => new Question(['question_type' => 'mcq', 'marks' => 1]),
        ]);
    }

    public function store(MultiQuestionRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($request, $exam);

        DB::transaction(function () use ($request, $exam): void {
            $exam->refresh();

            foreach (array_values($request->input('questions', [])) as $questionData) {
                $question = new Question;
                $question->fill([
                    'question_text' => trim($questionData['question_text']),
                    'question_type' => $questionData['question_type'] ?? 'mcq',
                    'marks' => $questionData['marks'] ?? $exam->marks_per_question,
                    'explanation' => $questionData['explanation'] ?? null,
                ]);
                $question->exam_id = $exam->id;
                $question->save();

                $correctIndex = (int) ($questionData['correct_option'] ?? 0);
                foreach (array_values($questionData['options'] ?? []) as $index => $option) {
                    $question->options()->create([
                        'option_text' => trim($option),
                        'is_correct' => $index === $correctIndex,
                        'position' => $index,
                    ]);
                }
            }

            $count = $exam->questions()->count();
            $exam->update([
                'total_marks' => (int) round($count * (float) $exam->marks_per_question),
            ]);
        });

        $count = count($request->input('questions', []));
        $message = $count > 1
            ? "{$count} questions added successfully."
            : 'Question added successfully.';

        return redirect()->route('branch.exams.show', $exam)->with('success', $message);
    }

    public function edit(Request $request, Question $question): View
    {
        $this->authorizeExam($request, $question->exam);

        return view('branch.questions.edit', [
            'branch' => $request->user()->branch,
            'exam' => $question->exam->load('schoolClass'),
            'question' => $question->load('options'),
        ]);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $this->authorizeExam($request, $question->exam);
        $this->saveQuestion($request, $question->exam, $question);

        return redirect()->route('branch.exams.show', $question->exam)->with('success', 'Question updated successfully.');
    }

    public function destroy(Request $request, Question $question): RedirectResponse
    {
        $this->authorizeExam($request, $question->exam);
        $exam = $question->exam;
        $question->delete();
        $exam->refresh();
        $exam->recalculateTotalMarks();

        return redirect()->route('branch.exams.show', $exam)->with('success', 'Question deleted successfully.');
    }

    private function saveQuestion(QuestionRequest $request, Exam $exam, Question $question): void
    {
        DB::transaction(function () use ($request, $exam, $question): void {
            $question->fill($request->safe()->only(['question_text', 'question_type', 'marks', 'explanation']));
            $question->exam_id = $exam->id;
            $question->save();

            $question->options()->delete();
            foreach (array_values($request->input('options', [])) as $index => $option) {
                $question->options()->create([
                    'option_text' => trim($option),
                    'is_correct' => $index === (int) $request->input('correct_option'),
                    'position' => $index,
                ]);
            }
        });
    }

    private function authorizeExam(Request $request, Exam $exam): void
    {
        abort_if(! $request->user()->branch_id || $exam->branch_id !== $request->user()->branch_id, 403, 'This exam does not belong to your branch.');
    }
}
