<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MultiQuestionRequest;
use App\Http\Requests\QuestionRequest;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $branch = $this->selectedBranch();

        if (! $branch) {
            return redirect()->route('admin.branch-selection.index')
                ->with('success', 'Please select a branch first to manage branch-related data.');
        }

        return view('admin.questions.index', [
            'selectedBranch' => $branch,
            'exams' => Exam::withCount('questions')->with('schoolClass')->forBranch($branch->id)->latest()->paginate(20),
        ]);
    }

    public function create(Exam $exam): View
    {
        $this->authorizeExam($exam);
        abort_if($exam->isPublished(), 403, 'Questions cannot be added to a published exam.');

        return view('admin.questions.create', [
            'selectedBranch' => $exam->branch,
            'exam' => $exam->load('schoolClass'),
            'question' => new Question(['question_type' => 'mcq', 'marks' => 1]),
        ]);
    }

    public function store(MultiQuestionRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);
        abort_if($exam->isPublished(), 403, 'Questions cannot be added to a published exam.');

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

            // Recalculate total marks based on actual question count
            $count = $exam->questions()->count();
            $exam->update([
                'total_marks' => (int) round($count * (float) $exam->marks_per_question),
            ]);
        });

        $count = count($request->input('questions', []));
        $message = $count > 1
            ? "{$count} questions added successfully."
            : 'Question added successfully.';

        return redirect()->route('admin.exams.show', $exam)->with('success', $message);
    }

    public function edit(Question $question): View
    {
        $this->authorizeExam($question->exam);
        abort_if($question->exam->isPublished(), 403, 'Questions cannot be edited in a published exam.');

        return view('admin.questions.edit', [
            'selectedBranch' => $question->exam->branch,
            'exam' => $question->exam->load('schoolClass'),
            'question' => $question->load('options'),
        ]);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $this->authorizeExam($question->exam);
        abort_if($question->exam->isPublished(), 403, 'Questions cannot be edited in a published exam.');
        $this->saveQuestion($request, $question->exam, $question);

        return redirect()->route('admin.exams.show', $question->exam)->with('success', 'Question updated successfully.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorizeExam($question->exam);
        abort_if($question->exam->isPublished(), 403, 'Questions cannot be deleted from a published exam.');
        $exam = $question->exam;
        $question->delete();
        $exam->refresh();
        $exam->recalculateTotalMarks();

        return redirect()->route('admin.exams.show', $exam)->with('success', 'Question deleted successfully.');
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

    private function selectedBranch(): ?Branch
    {
        $branchId = session('admin_selected_branch_id');

        return $branchId ? Branch::find($branchId) : null;
    }

    private function authorizeExam(Exam $exam): void
    {
        $branch = $this->selectedBranch();

        abort_if(! $branch || $exam->branch_id !== $branch->id, 403, 'This exam is not in the selected branch.');
    }
}
