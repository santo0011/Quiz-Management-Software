<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Log;

/**
 * Records login attempts (success and failure) to the persistent
 * `login_logs` table for the Super Admin audit log. Every call is wrapped
 * so a logging failure (e.g. a DB hiccup) can never break the actual login
 * flow it's attached to — it only ever degrades to a warning in the
 * application log.
 */
class LoginLogger
{
    public static function success(
        string $role,
        string $loginMethod,
        ?string $name,
        ?string $identifier,
        ?int $subjectId,
        ?Branch $branch = null,
    ): void {
        self::record($role, $loginMethod, LoginLog::STATUS_SUCCESS, null, $name, $identifier, $subjectId, $branch);
    }

    public static function failed(
        string $role,
        string $loginMethod,
        string $reason,
        ?string $name = null,
        ?string $identifier = null,
        ?int $subjectId = null,
        ?Branch $branch = null,
    ): void {
        self::record($role, $loginMethod, LoginLog::STATUS_FAILED, $reason, $name, $identifier, $subjectId, $branch);
    }

    private static function record(
        string $role,
        string $loginMethod,
        string $status,
        ?string $failureReason,
        ?string $name,
        ?string $identifier,
        ?int $subjectId,
        ?Branch $branch,
    ): void {
        try {
            LoginLog::create([
                'role' => $role,
                'name' => $name,
                'identifier' => $identifier,
                'subject_id' => $subjectId,
                'branch_id' => $branch?->id,
                'branch_name' => $branch?->name,
                'login_method' => $loginMethod,
                'status' => $status,
                'failure_reason' => $failureReason,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record login log.', [
                'role' => $role,
                'status' => $status,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
