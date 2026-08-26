<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolClassRequest;
use App\Models\Branch;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolClassController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $request->integer('branch_id') ?: null;

        $classes = SchoolClass::with('branch')
            ->when($branchId, fn ($query) => $query->visibleToBranch($branchId))
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.classes.index', [
            'branches' => Branch::orderBy('name')->get(),
            'selectedBranchId' => $branchId,
            'class' => new SchoolClass,
            'classes' => $classes,
            'filters' => $request->only(['search', 'branch_id']),
        ]);
    }

    public function create(): View
    {
        return view('admin.classes.create', [
            'branches' => Branch::orderBy('name')->get(),
            'class' => new SchoolClass,
        ]);
    }

    public function store(SchoolClassRequest $request): RedirectResponse
    {
        SchoolClass::create($request->validated());

        return redirect()->route('admin.classes.index')->with('success', 'Grade added successfully.');
    }

    public function show(SchoolClass $class): View
    {
        return view('admin.classes.show', [
            'class' => $class->load('branch'),
        ]);
    }

    public function edit(SchoolClass $class): View
    {
        return view('admin.classes.edit', [
            'selectedBranch' => $class->branch,
            'class' => $class,
        ]);
    }

    public function update(SchoolClassRequest $request, SchoolClass $class): RedirectResponse
    {
        $class->update($request->validated());

        return redirect()->route('admin.classes.index')->with('success', 'Grade updated successfully.');
    }

    public function destroy(SchoolClass $class): RedirectResponse
    {
        $hasRelatedData = $class->students()->exists() || $class->exams()->exists();

        if ($hasRelatedData) {
            return redirect()->route('admin.classes.index')
                ->with('error', 'This grade cannot be deleted because it has related students or exams. Please deactivate the students instead.');
        }

        $class->delete();

        return redirect()->route('admin.classes.index')->with('success', 'Grade deleted successfully.');
    }
}
