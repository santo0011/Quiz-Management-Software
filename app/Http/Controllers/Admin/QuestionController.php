<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'exams' => Exam::withCount('questions')->with('schoolClass')->forBranch($branch->id)->latest()->paginate(10),
        ]);
    }

    public function create(Exam $exam): View
    {
        $this->authorizeExam($exam);

        return view('admin.questions.create', [
            'selectedBranch' => $exam->branch,
            'exam' => $exam->load('schoolClass'),
            'question' => new Question(['question_type' => 'mcq', 'marks' => 1]),
        ]);
    }

    public function store(QuestionRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);
        $this->saveQuestion($request, $exam, new Question);

        return redirect()->route('admin.exams.show', $exam)->with('success', 'Question added successfully.');
    }

    public function edit(Question $question): View
    {
        $this->authorizeExam($question->exam);

        return view('admin.questions.edit', [
            'selectedBranch' => $question->exam->branch,
            'exam' => $question->exam->load('schoolClass'),
            'question' => $question->load('options'),
        ]);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $this->authorizeExam($question->exam);
        $this->saveQuestion($request, $question->exam, $question);

        return redirect()->route('admin.exams.show', $question->exam)->with('success', 'Question updated successfully.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorizeExam($question->exam);
        $exam = $question->exam;
        $question->delete();

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
