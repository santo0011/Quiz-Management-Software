<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Locks in the production safety net from AppServiceProvider — these
 * commands would drop/wipe every table (every Student, Exam, Question,
 * Result, ...), so they must be refused outright once APP_ENV=production,
 * regardless of --force.
 *
 * Laravel deliberately skips wiring the Symfony console event bridge that
 * fires CommandStarting when runningUnitTests() is true (see
 * Illuminate\Foundation\Console\Kernel::__construct()), so Artisan::call()
 * can never trigger this guard from inside a test process — there's nothing
 * to catch. These tests instead spawn a real `php artisan` subprocess
 * (exactly how it runs in a real deploy) against a disposable temp SQLite
 * database, so the real code path is what's actually exercised.
 */
class ProductionDestructiveCommandGuardTest extends TestCase
{
    private string $dbPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbPath = tempnam(sys_get_temp_dir(), 'guard_test_').'.sqlite';
        touch($this->dbPath);
    }

    protected function tearDown(): void
    {
        @unlink($this->dbPath);

        parent::tearDown();
    }

    #[DataProvider('blockedCommands')]
    public function test_blocked_in_production(string $command): void
    {
        $process = $this->runArtisan($command, 'production');

        $this->assertNotSame(0, $process->getExitCode());
        $this->assertStringContainsString("BLOCKED: \"{$command}\" is disabled in production", $process->getOutput().$process->getErrorOutput());
    }

    public static function blockedCommands(): array
    {
        return [
            ['migrate:fresh'],
            ['migrate:refresh'],
            ['migrate:reset'],
            ['db:wipe'],
        ];
    }

    public function test_plain_migrate_is_not_blocked_in_production(): void
    {
        $process = $this->runArtisan('migrate', 'production');

        $this->assertSame(0, $process->getExitCode());
        $this->assertStringNotContainsString('BLOCKED', $process->getOutput().$process->getErrorOutput());
    }

    public function test_migrate_fresh_is_not_blocked_outside_production(): void
    {
        $process = $this->runArtisan('migrate:fresh', 'local');

        $this->assertSame(0, $process->getExitCode());
        $this->assertStringNotContainsString('BLOCKED', $process->getOutput().$process->getErrorOutput());
    }

    private function runArtisan(string $command, string $env): Process
    {
        $process = new Process(
            ['php', 'artisan', $command, '--force'],
            base_path(),
            [
                'APP_ENV' => $env,
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $this->dbPath,
            ]
        );
        $process->run();

        return $process;
    }
}
