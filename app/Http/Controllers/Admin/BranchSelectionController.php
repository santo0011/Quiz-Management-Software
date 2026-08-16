<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchSelectionController extends Controller
{
    public function index(): View
    {
        $selectedBranch = $this->selectedBranch();

        return view('admin.branch-selection.index', [
            'branches' => Branch::orderBy('name')->get(),
            'selectedBranch' => $selectedBranch,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
        ], [
            'branch_id.required' => 'Please select a branch.',
            'branch_id.exists' => 'The selected branch could not be found.',
        ]);

        $request->session()->put('admin_selected_branch_id', (int) $validated['branch_id']);

        return redirect()->route('admin.students.index')->with('success', 'Current branch updated successfully.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_selected_branch_id');

        return redirect()->route('admin.branch-selection.index')->with('success', 'Current branch selection cleared.');
    }

    private function selectedBranch(): ?Branch
    {
        $branchId = session('admin_selected_branch_id');

        return $branchId ? Branch::find($branchId) : null;
    }
}
