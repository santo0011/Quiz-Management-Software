<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function exams(): View
    {
        return $this->showModule('Exams', 'bi-journal-check');
    }

    public function questions(): View
    {
        return $this->showModule('Questions', 'bi-patch-question-fill');
    }

    public function results(): View
    {
        return $this->showModule('Results', 'bi-bar-chart-fill');
    }

    private function showModule(string $module, string $icon): View
    {
        return view('admin.modules.show', [
            'module' => $module,
            'icon' => $icon,
        ]);
    }
}
