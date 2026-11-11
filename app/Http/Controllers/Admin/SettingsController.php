<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use App\Services\ExamDataResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => Setting::current(),
        ]);
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        $settings = Setting::current();
        $validated = $request->validated();

        unset($validated['logo']);

        if (! $request->filled('mail_password')) {
            unset($validated['mail_password']);
        }

        if (! $request->filled('common_student_password')) {
            unset($validated['common_student_password']);
        }

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('branding', 'public');
        }

        $settings->update($validated);

        return redirect()->route('admin.settings.edit')->with('success', 'Settings updated successfully.');
    }

    /**
     * Delete every exam, question, option, passage, attempt, answer and
     * question category. Restricted to the single Super Admin allowed by
     * User::canClearExamData(), and confirmed with their password plus
     * typing CLEAR so it can't be triggered by accident.
     */
    public function clearExamData(Request $request, ExamDataResetService $resetService): RedirectResponse
    {
        abort_unless($request->user()->canClearExamData(), 403);

        $request->validateWithBag('clearExamData', [
            'clear_password' => ['required', 'current_password:web'],
            'clear_confirmation' => ['required', 'in:CLEAR'],
        ], [
            'clear_password.current_password' => 'The password is incorrect.',
            'clear_confirmation.in' => 'Type CLEAR (in capital letters) to confirm.',
        ]);

        $counts = $resetService->clear();

        Log::warning('Exam data cleared by Super Admin.', [
            'user_id' => $request->user()->id,
            'email' => $request->user()->email,
            'deleted' => $counts,
        ]);

        return redirect()->route('admin.settings.edit')->with(
            'success',
            "Exam data cleared: {$counts['exams']} exams, {$counts['questions']} questions, {$counts['exam_attempts']} attempts and {$counts['question_categories']} categories deleted."
        );
    }
}
