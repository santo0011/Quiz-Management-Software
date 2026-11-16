<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only exam browser for Teachers: the exams of their branch (its own
 * exams plus the Super Admin's global ones) and each exam's questions.
 * Teachers can never create, edit, publish or delete anything here.
 */
class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user('teacher');

        $exams = Exam::with(['grades', 'subject'])
            ->withCount('questions')
            ->visibleToBranch($teacher->branch_id)
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search')->toString().'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('teacher.exams.index', [
            'teacher' => $teacher->load('branch'),
            'exams' => $exams,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Request $request, Exam $exam): View
    {
        $teacher = $request->user('teacher');

        abort_if(
            $exam->branch_id !== null && $exam->branch_id !== $teacher->branch_id,
            403,
            'This exam does not belong to your branch.'
        );

        return view('teacher.exams.show', [
            'exam' => $exam->load(['grades', 'subject', 'questions.options', 'questions.category']),
        ]);
    }
}
