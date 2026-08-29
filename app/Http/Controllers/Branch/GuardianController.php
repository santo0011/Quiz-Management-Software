<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    /**
     * Powers the "Existing Guardian" search box on the Student Create
     * form. Scoped to guardians who already have at least one student in
     * this Branch user's own branch, so Branch staff never see another
     * branch's guardian while creating a student (unlike Super Admin,
     * which searches every guardian).
     */
    public function search(Request $request): JsonResponse
    {
        $branch = $request->user()->branch;
        abort_if(! $branch, 403, 'Your account is not linked to a branch.');

        $search = trim((string) $request->query('q', ''));

        $guardians = Guardian::query()
            ->whereHas('linkedStudents', fn ($query) => $query->where('branch_id', $branch->id))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('name IS NULL')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return response()->json($guardians->map(fn (Guardian $guardian) => [
            'id' => $guardian->id,
            'name' => $guardian->name ?: 'Guardian',
            'email' => $guardian->email,
        ]));
    }
}
