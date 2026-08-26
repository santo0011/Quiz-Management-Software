<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamCategoryRequest;
use App\Http\Requests\ExamRequest;
use App\Http\Requests\ExamSettingsRequest;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Services\AcademicSessionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $request->integer('branch_id') ?: null;
        $selectedSessionId = AcademicSessionResolver::selectedId($request);

        $exams = $selectedSessionId
            ? Exam::with(['schoolClass', 'subject', 'questions'])
                ->where('session_id', $selectedSessionId)
                ->when($branchId, fn ($query) => $query->forBranch($branchId))
                ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search')->toString().'%'))
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
                ->latest()
                ->paginate(20)
                ->withQueryString()
            : null;

        return view('admin.exams.index', [
            'branches' => Branch::orderBy('name')->get(),
            'selectedBranchId' => $branchId,
            'selectedSessionId' => $selectedSessionId,
            'exam' => new Exam(['status' => Exam::STATUS_DRAFT, 'maximum_attempts' => 1, 'marks_per_question' => 1]),
            'exams' => $exams,
            'classes' => $branchId
                ? SchoolClass::visibleToBranch($branchId)->orderBy('name')->get()
                : collect(),
            'subjects' => Subject::orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'branch_id']),
        ]);
    }

    public function store(ExamRequest $request): RedirectResponse
    {
        $selectedSessionId = AcademicSessionResolver::selectedId($request);
        abort_if(! $selectedSessionId, 403, 'Please select an academic session first.');

        Exam::create($request->validated() + [
            'status' => Exam::STATUS_DRAFT,
            'total_marks' => 0,
            'marks_per_question' => 1,
            'duration_minutes' => 30,
            'session_id' => $selectedSessionId,
        ]);

        return redirect()->route('admin.exams.index')->with('success', 'Exam created successfully.');
    }

    public function show(Request $request, Exam $exam): View
    {
        $this->authorizeSessionScope($request, $exam);

        return view('admin.exams.show', [
            'selectedBranch' => $exam->branch,
            'exam' => $exam->load(['schoolClass', 'subject', 'questions.options']),
        ]);
    }

    public function edit(Request $request, Exam $exam): View
    {
        $this->authorizeSessionScope($request, $exam);
        abort_if($exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        return view('admin.exams.edit', [
            'selectedBranch' => $exam->branch,
            'exam' => $exam,
            'classes' => SchoolClass::visibleToBranch($exam->branch_id)->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }

    public function update(ExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorizeSessionScope($request, $exam);
        abort_if($exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        $exam->update($request->validated());

        return redirect()->route('admin.exams.index')->with('success', 'Exam updated successfully.');
    }

    public function updateSettings(ExamSettingsRequest $request, Exam $exam): RedirectResponse
    {
        $exam->update($request->validated());
        $exam->recalculateTotalMarks();

        return redirect()->route('admin.exams.show', $exam)->with('success', 'Exam settings updated successfully.');
    }

    public function updateCategory(ExamCategoryRequest $request, Exam $exam): RedirectResponse
    {
        $exam->update($request->validated());

        return redirect()->route('admin.questions.create', $exam)->with('success', 'Question category saved successfully.');
    }

    public function reorderItems(Request $request, Exam $exam): RedirectResponse
    {
        abort_if($exam->hasBeenAttempted(), 403, Exam::LOCK_MESSAGE);

        $validated = $request->validate([
            'type' => ['required', 'in:question,passage_group'],
            'id' => ['required', 'integer'],
            'direction' => ['required', 'in:up,down'],
        ]);

        $this->swapItemPosition($exam, $validated['type'], (int) $validated['id'], $validated['direction']);

        return redirect()->route('admin.exams.show', $exam)->with('success', 'Order updated successfully.');
    }

    private function swapItemPosition(Exam $exam, string $type, int $id, string $direction): void
    {
        if ($type === 'question') {
            $question = $exam->questions()->find($id);

            if ($question && $question->passage_group_id) {
                $this->swapWithinSiblings($question->passageGroup->questions()->orderBy('position')->get(), $id, $direction);

                return;
            }
        }

        $items = $exam->orderedItems();

        $currentIndex = $items->search(fn (array $item) => $item['type'] === $type
            && ($item['type'] === 'question' ? $item['question']->id : $item['group']->id) === $id);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if (! $items->has($targetIndex)) {
            return;
        }

        $currentModel = $items[$currentIndex]['type'] === 'question' ? $items[$currentIndex]['question'] : $items[$currentIndex]['group'];
        $targetModel = $items[$targetIndex]['type'] === 'question' ? $items[$targetIndex]['question'] : $items[$targetIndex]['group'];

        [$currentModel->position, $targetModel->position] = [$targetModel->position, $currentModel->position];
        $currentModel->save();
        $targetModel->save();
    }

    private function swapWithinSiblings(\Illuminate\Support\Collection $siblings, int $id, string $direction): void
    {
        $siblings = $siblings->values();
        $currentIndex = $siblings->search(fn ($item) => $item->id === $id);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if (! $siblings->has($targetIndex)) {
            return;
        }

        $current = $siblings[$currentIndex];
        $target = $siblings[$targetIndex];

        [$current->position, $target->position] = [$target->position, $current->position];
        $current->save();
        $target->save();
    }

    public function destroy(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeSessionScope($request, $exam);

        if ($exam->hasBeenAttempted()) {
            return redirect()->route('admin.exams.index')
                ->with('error', Exam::LOCK_MESSAGE);
        }

        $exam->delete();

        return redirect()->route('admin.exams.index')->with('success', 'Exam deleted successfully.');
    }

    public function publish(Request $request, Exam $exam): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        if ($exam->isPublished()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'This exam is already published.'], 422);
            }

            abort(422, 'This exam is already published.');
        }

        $exam->update(['status' => Exam::STATUS_PUBLISHED]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Exam published successfully.']);
        }

        return redirect()->route('admin.exams.index')->with('success', 'Exam published successfully.');
    }

    public function unpublish(Request $request, Exam $exam): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        if (! $exam->isPublished()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'This exam is not published.'], 422);
            }

            abort(422, 'This exam is not published.');
        }

        // Backend check: if any student has attended/started the exam, unpublishing is not allowed.
        if ($exam->hasBeenAttempted()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => Exam::UNPUBLISH_LOCK_MESSAGE], 422);
            }

            return redirect()->route('admin.exams.show', $exam)
                ->with('error', Exam::UNPUBLISH_LOCK_MESSAGE);
        }

        $exam->update(['status' => Exam::STATUS_DRAFT]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Exam unpublished successfully.']);
        }

        return redirect()->route('admin.exams.show', $exam)->with('success', 'Exam unpublished successfully.');
    }

    /**
     * Block reaching an Exam that belongs to a different Academic Session
     * than the one currently selected. Legacy rows (either side null)
     * fall through as allowed.
     */
    private function authorizeSessionScope(Request $request, Exam $exam): void
    {
        $selectedSessionId = AcademicSessionResolver::selectedId($request);

        abort_if(
            $exam->session_id !== null && $selectedSessionId !== null && $exam->session_id !== $selectedSessionId,
            403,
            'This exam does not belong to the currently selected academic session.'
        );
    }
}
