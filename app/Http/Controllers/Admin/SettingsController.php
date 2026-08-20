<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
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

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('branding', 'public');
        }

        $settings->update($validated);

        return redirect()->route('admin.settings.edit')->with('success', 'Settings updated successfully.');
    }
}
