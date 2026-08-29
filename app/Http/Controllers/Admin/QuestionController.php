<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MultiQuestionRequest;
use App\Http\Requests\QuestionRequest;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\PassageGroup;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $request->integer('branch_id') ?: null;

        return view('admin.questions.index', [
            'branches' => Branch::orderBy('name')->get(),
            'selectedBranchId' => $branchId,
            'exams' => Exam::withCount('questions')->with('schoolClass')
                ->when($branchId, fn ($query) => $query->visibleToBranch($branchId))
                ->latest()->paginate(20),
            'filters' => $request->only(['branch_id']),
        ]);
    }

    public function create(Exam $exam): View
    {
        return view('admin.questions.create', [
            'selectedBranch' => $exam->branch,
            'exam' => $exam->load('schoolClass', 'questions.options', 'category'),
            'question' => new Question(['question_type' => 'mcq', 'marks' => 1]),
            'categories' => QuestionCategory::visibleToBranch($exam->branch_id)->orderBy('name')->get(),
        ]);
    }

    public function store(MultiQuestionRequest $request, Exam $exam): RedirectResponse
    {
        $count = $this->createQuestionsFromRequest($request, $exam);
        $message = $count > 1
            ? "{$count} questions added successfully."
            : 'Question added successfully.';

        return redirect()->route('admin.questions.create', $exam)->with('success', $message);
    }

    public function createForPassage(Exam $exam, PassageGroup $passageGroup): View
    {
        abort_if($passageGroup->exam_id !== $exam->id, 404);

        return view('admin.passage-groups.questions-create', [
            'selectedBranch' => $exam->branch,
            'exam' => $exam->load('schoolClass', 'category'),
            'passageGroup' => $passageGroup->load('questions.options', 'questions.category'),
            'question' => new Question(['question_type' => 'mcq', 'marks' => 1]),
            'categories' => QuestionCategory::visibleToBranch($exam->branch_id)->orderBy('name')->get(),
        ]);
    }

    public function storeForPassage(MultiQuestionRequest $request, Exam $exam, PassageGroup $passageGroup): RedirectResponse
    {
        abort_if($passageGroup->exam_id !== $exam->id, 404);

        $count = $this->createQuestionsFromRequest($request, $exam, $passageGroup);
        $message = $count > 1
            ? "{$count} questions added to the passage successfully."
            : 'Question added to the passage successfully.';

        return redirect()->route('admin.questions.create', $exam)->with('success', $message);
    }

    private function createQuestionsFromRequest(MultiQuestionRequest $request, Exam $exam, ?PassageGroup $passageGroup = null): int
    {
        return DB::transaction(function () use ($request, $exam, $passageGroup): int {
            $exam->refresh();
            $position = $passageGroup
                ? (int) $passageGroup->questions()->max('position') + 1
                : $exam->nextTopLevelPosition();

            $questionsPayload = array_values($request->input('questions', []));

            foreach ($questionsPayload as $questionData) {
                $question = new Question;
                $question->fill([
                    'question_text' => trim($questionData['question_text']),
                    'question_type' => 'mcq',
                    'marks' => $questionData['marks'] ?? $exam->marks_per_question,
                    'explanation' => $questionData['explanation'] ?? null,
                    'position' => $position++,
                ]);
                $question->exam_id = $exam->id;
                $question->question_category_id = $questionData['question_category_id'] ?? null;
                $question->passage_group_id = $passageGroup?->id;
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

            $exam->recalculateTotalMarks();

            return count($questionsPayload);
        });
    }

    public function edit(Question $question): View
    {
        return view('admin.questions.edit', [
            'selectedBranch' => $question->exam->branch,
            'exam' => $question->exam->load('schoolClass', 'category'),
            'question' => $question->load('options'),
            'categories' => QuestionCategory::visibleToBranch($question->exam->branch_id)->orderBy('name')->get(),
        ]);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $exam = $question->exam;
        $this->saveQuestion($request, $exam, $question);
        $exam->recalculateTotalMarks();

        return redirect()->route('admin.questions.create', $exam)->with('success', 'Question updated successfully.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $exam = $question->exam;
        $question->delete();
        $exam->refresh();
        $exam->recalculateTotalMarks();

        return redirect()->route('admin.questions.create', $exam)->with('success', 'Question deleted successfully.');
    }

    private function saveQuestion(QuestionRequest $request, Exam $exam, Question $question): void
    {
        DB::transaction(function () use ($request, $exam, $question): void {
            $question->fill($request->safe()->only(['question_text', 'marks', 'explanation']));
            $question->question_type = 'mcq';
            $question->exam_id = $exam->id;
            $question->question_category_id = $request->input('question_category_id');
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

}
