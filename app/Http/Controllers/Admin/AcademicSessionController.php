<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicSessionRequest;
use App\Models\AcademicSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicSessionController extends Controller
{
    public function index(Request $request): View
    {
        $sessions = AcademicSession::withCount(['students', 'exams', 'examAttempts'])
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->latest('start_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.academic-sessions.index', [
            'academicSession' => new AcademicSession(['is_active' => true]),
            'sessions' => $sessions,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): View
    {
        return view('admin.academic-sessions.create', [
            'academicSession' => new AcademicSession(['is_active' => true]),
        ]);
    }

    public function store(AcademicSessionRequest $request): RedirectResponse
    {
        AcademicSession::create($request->validated());

        return redirect()->route('admin.academic-sessions.index')->with('success', 'Academic session added successfully.');
    }

    public function show(AcademicSession $academicSession): View
    {
        return view('admin.academic-sessions.show', [
            'academicSession' => $academicSession,
        ]);
    }

    public function edit(AcademicSession $academicSession): View
    {
        return view('admin.academic-sessions.edit', [
            'academicSession' => $academicSession,
        ]);
    }

    public function update(AcademicSessionRequest $request, AcademicSession $academicSession): RedirectResponse
    {
        $academicSession->update($request->validated());

        return redirect()->route('admin.academic-sessions.index')->with('success', 'Academic session updated successfully.');
    }

    public function destroy(AcademicSession $academicSession): RedirectResponse
    {
        if ($academicSession->hasRelatedData()) {
            return redirect()->route('admin.academic-sessions.index')
                ->with('error', AcademicSession::DELETE_LOCK_MESSAGE);
        }

        $academicSession->delete();

        return redirect()->route('admin.academic-sessions.index')->with('success', 'Academic session deleted successfully.');
    }

    public function toggleActive(AcademicSession $academicSession): RedirectResponse
    {
        $academicSession->update(['is_active' => ! $academicSession->is_active]);

        $message = $academicSession->is_active
            ? 'Academic session activated successfully.'
            : 'Academic session closed successfully.';

        return redirect()->route('admin.academic-sessions.index')->with('success', $message);
    }
}
