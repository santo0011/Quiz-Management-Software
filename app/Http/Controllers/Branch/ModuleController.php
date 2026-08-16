<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function exams(Request $request): View
    {
        return $this->showBranchModule($request, 'Exams', 'bi-journal-check');
    }

    public function questions(Request $request): View
    {
        return $this->showBranchModule($request, 'Questions', 'bi-patch-question-fill');
    }

    public function results(Request $request): View
    {
        return $this->showBranchModule($request, 'Results', 'bi-bar-chart-fill');
    }

    private function showBranchModule(Request $request, string $module, string $icon): View
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        return view('branch.modules.show', [
            'branch' => $branch,
            'module' => $module,
            'icon' => $icon,
        ]);
    }
}
