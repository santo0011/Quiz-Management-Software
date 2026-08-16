<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolClassRequest;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolClassController extends Controller
{
    public function index(Request $request): View
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        $classes = SchoolClass::with('branch')
            ->where('branch_id', $branch->id)
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('branch.classes.index', [
            'branch' => $branch,
            'class' => new SchoolClass,
            'classes' => $classes,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(Request $request): View
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        return view('branch.classes.create', [
            'branch' => $branch,
            'class' => new SchoolClass,
        ]);
    }

    public function store(SchoolClassRequest $request): RedirectResponse
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        SchoolClass::create($request->validated() + [
            'branch_id' => $branch->id,
        ]);

        return redirect()->route('branch.classes.index')->with('success', 'Class added successfully.');
    }

    public function show(Request $request, SchoolClass $class): View
    {
        $this->authorizeBranchClass($request, $class);

        return view('branch.classes.show', [
            'branch' => $request->user()->branch,
            'class' => $class,
        ]);
    }

    public function edit(Request $request, SchoolClass $class): View
    {
        $this->authorizeBranchClass($request, $class);

        return view('branch.classes.edit', [
            'branch' => $request->user()->branch,
            'class' => $class,
        ]);
    }

    public function update(SchoolClassRequest $request, SchoolClass $class): RedirectResponse
    {
        $this->authorizeBranchClass($request, $class);

        $class->update($request->validated());

        return redirect()->route('branch.classes.index')->with('success', 'Class updated successfully.');
    }

    public function destroy(Request $request, SchoolClass $class): RedirectResponse
    {
        $this->authorizeBranchClass($request, $class);
        $class->delete();

        return redirect()->route('branch.classes.index')->with('success', 'Class deleted successfully.');
    }

    private function authorizeBranchClass(Request $request, SchoolClass $class): void
    {
        abort_if(! $request->user()->branch_id || $class->branch_id !== $request->user()->branch_id, 403, 'This class does not belong to your branch.');
    }
}
