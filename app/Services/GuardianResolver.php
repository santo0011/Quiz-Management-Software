<?php

namespace App\Services;

use App\Models\Guardian;

/**
 * Turns a Student form submission's guardian_type ("new" / "existing")
 * into the guardian_id/guardian_name/guardian_email that actually get
 * saved on the Student row, per Guardian Selection on the Student Create
 * form: reuse a Guardian account by email instead of ever duplicating one,
 * and never trust a client-submitted name/email for an "existing" pick —
 * always re-derive both from the selected Guardian's own record.
 */
class GuardianResolver
{
    public static function resolve(array $validated): array
    {
        $guardianType = $validated['guardian_type'] ?? null;
        unset($validated['guardian_type']);

        if ($guardianType === 'existing') {
            $guardian = Guardian::findOrFail($validated['guardian_id']);

            $validated['guardian_id'] = $guardian->id;
            $validated['guardian_name'] = $guardian->name ?: ($validated['guardian_name'] ?? '');
            $validated['guardian_email'] = $guardian->email;

            return $validated;
        }

        if ($guardianType === 'new') {
            $email = strtolower(trim($validated['guardian_email']));
            $name = trim($validated['guardian_name'] ?? '');

            $guardian = Guardian::firstOrCreate(['email' => $email], ['name' => $name]);

            if (blank($guardian->name) && filled($name)) {
                $guardian->forceFill(['name' => $name])->save();
            }

            $validated['guardian_id'] = $guardian->id;
            $validated['guardian_email'] = $guardian->email;
            $validated['guardian_name'] = $guardian->name ?: $name;

            return $validated;
        }

        return $validated;
    }
}
