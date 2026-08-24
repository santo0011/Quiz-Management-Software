<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Pins down the config that makes Student/Branch logins survive 7 days of
 * browser restarts (session.lifetime + expire_on_close, and each guard's
 * "remember me" cookie duration). These values are what actually produced
 * the confirmed browser-level behavior — a config edit here silently
 * shortening the window is the most likely way this regresses.
 */
class PersistentLoginConfigTest extends TestCase
{
    public function test_session_lifetime_is_seven_days(): void
    {
        $this->assertSame(10080, config('session.lifetime'), 'Session lifetime must stay at 7 days (10080 minutes).');
    }

    public function test_session_does_not_expire_when_the_browser_closes(): void
    {
        $this->assertFalse(config('session.expire_on_close'), 'expire_on_close must stay false so the session cookie survives closing the browser.');
    }

    public function test_student_guard_remembers_for_seven_days(): void
    {
        $this->assertSame(10080, config('auth.guards.student.remember'), 'Student "remember me" cookie must stay at 7 days.');
    }

    public function test_web_guard_remembers_for_seven_days(): void
    {
        // Branch accounts authenticate on the "web" guard (shared with Super
        // Admin — there is no separate "branch" guard in this app).
        $this->assertSame(10080, config('auth.guards.web.remember'), 'Web guard "remember me" cookie (used by Branch) must stay at 7 days.');
    }
}
