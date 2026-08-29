<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    /**
     * Powers the "Existing Guardian" search box on the Student Create
     * form. Super Admin can link a Student to any Guardian account
     * system-wide (unlike Branch, which is scoped to its own branch).
     */
    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $guardians = Guardian::query()
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
