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
    public function index(Request $request): View|RedirectResponse
    {
        $branch = $this->selectedBranch();

        if (! $branch) {
            return redirect()->route('admin.branch-selection.index')
                ->with('success', 'Please select a branch first to manage branch-related data.');
        }

        $classes = SchoolClass::with('branch')
            ->where('branch_id', $branch->id)
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.classes.index', [
            'selectedBranch' => $branch,
            'class' => new SchoolClass,
            'classes' => $classes,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $branch = $this->selectedBranch();

        if (! $branch) {
            return redirect()->route('admin.branch-selection.index')
                ->with('success', 'Please select a branch first to manage branch-related data.');
        }

        return view('admin.classes.create', [
            'selectedBranch' => $branch,
            'class' => new SchoolClass,
        ]);
    }

    public function store(SchoolClassRequest $request): RedirectResponse
    {
        $branch = $this->selectedBranch();

        if (! $branch) {
            return redirect()->route('admin.branch-selection.index');
        }

        SchoolClass::create($request->validated() + [
            'branch_id' => $branch->id,
        ]);

        return redirect()->route('admin.classes.index')->with('success', 'Class added successfully.');
    }

    public function show(SchoolClass $class): View
    {
        $this->authorizeSelectedBranch($class);

        return view('admin.classes.show', [
            'class' => $class->load('branch'),
        ]);
    }

    public function edit(SchoolClass $class): View
    {
        $this->authorizeSelectedBranch($class);

        return view('admin.classes.edit', [
            'selectedBranch' => $class->branch,
            'class' => $class,
        ]);
    }

    public function update(SchoolClassRequest $request, SchoolClass $class): RedirectResponse
    {
        $this->authorizeSelectedBranch($class);

        $class->update($request->validated());

        return redirect()->route('admin.classes.index')->with('success', 'Class updated successfully.');
    }

    public function destroy(SchoolClass $class): RedirectResponse
    {
        $this->authorizeSelectedBranch($class);

        $class->delete();

        return redirect()->route('admin.classes.index')->with('success', 'Class deleted successfully.');
    }

    private function selectedBranch(): ?Branch
    {
        $branchId = session('admin_selected_branch_id');

        return $branchId ? Branch::find($branchId) : null;
    }

    private function authorizeSelectedBranch(SchoolClass $class): void
    {
        $branch = $this->selectedBranch();

        abort_if(! $branch || $class->branch_id !== $branch->id, 403, 'This class is not in the selected branch.');
    }
}
