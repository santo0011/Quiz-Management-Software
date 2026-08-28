<?php

namespace App\Console\Commands;

use App\Models\ExamAttempt;
use App\Services\ExamAttemptService;
use Illuminate\Console\Command;

/**
 * Finalizes exam attempts whose time ran out while the student was away
 * (closed the tab, lost connection, never came back). The exam runner also
 * auto-submits an expired attempt the moment its own state is next fetched,
 * but that only fires if someone actually revisits it — this command is the
 * guarantee that "once time expires, the exam is submitted" holds even if
 * the student never returns at all.
 */
class SubmitExpiredExamAttempts extends Command
{
    protected $signature = 'exams:submit-expired';

    protected $description = 'Auto-submit in-progress exam attempts whose time has run out';

    public function handle(ExamAttemptService $service): int
    {
        $expired = ExamAttempt::with('student')
            ->where('status', 'in_progress')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $attempt) {
            if (! $attempt->student) {
                continue;
            }

            $service->submit($attempt, $attempt->student);
        }

        $this->info("Auto-submitted {$expired->count()} expired exam attempt(s).");

        return self::SUCCESS;
    }
}
