<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
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

    public function store(QuestionRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($request, $exam);
        $this->saveQuestion($request, $exam, new Question);

        return redirect()->route('branch.exams.show', $exam)->with('success', 'Question added successfully.');
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
