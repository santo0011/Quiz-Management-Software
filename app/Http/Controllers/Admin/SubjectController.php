<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubjectRequest;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $subjects = Subject::when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.subjects.index', [
            'subject' => new Subject,
            'subjects' => $subjects,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): View
    {
        return view('admin.subjects.create', [
            'subject' => new Subject,
        ]);
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        Subject::create($request->validated());

        return redirect()->route('admin.subjects.index')->with('success', 'Subject added successfully.');
    }

    public function show(Subject $subject): View
    {
        return view('admin.subjects.show', [
            'subject' => $subject,
        ]);
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.edit', [
            'subject' => $subject,
        ]);
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->validated());

        return redirect()->route('admin.subjects.index')->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $hasRelatedData = $subject->students()->exists() || $subject->exams()->exists();

        if ($hasRelatedData) {
            return redirect()->route('admin.subjects.index')
                ->with('error', 'This subject cannot be deleted because it is assigned to students or exams.');
        }

        $subject->delete();

        return redirect()->route('admin.subjects.index')->with('success', 'Subject deleted successfully.');
    }
}
