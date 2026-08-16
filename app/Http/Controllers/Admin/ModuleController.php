<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function exams(): View|RedirectResponse
    {
        return $this->showBranchModule('Exams', 'bi-journal-check');
    }

    public function questions(): View|RedirectResponse
    {
        return $this->showBranchModule('Questions', 'bi-patch-question-fill');
    }

    public function results(): View|RedirectResponse
    {
        return $this->showBranchModule('Results', 'bi-bar-chart-fill');
    }

    private function showBranchModule(string $module, string $icon): View|RedirectResponse
    {
        $branch = $this->selectedBranch();

        if (! $branch) {
            return redirect()->route('admin.branch-selection.index')
                ->with('success', 'Please select a branch first to manage branch-related data.');
        }

        return view('admin.modules.show', [
            'selectedBranch' => $branch,
            'module' => $module,
            'icon' => $icon,
        ]);
    }

    private function selectedBranch(): ?Branch
    {
        $branchId = session('admin_selected_branch_id');

        return $branchId ? Branch::find($branchId) : null;
    }
}
