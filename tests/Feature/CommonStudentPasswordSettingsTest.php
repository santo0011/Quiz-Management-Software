<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CommonStudentPasswordSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_set_the_common_student_password(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), ['common_student_password' => 'override-secret'])
            ->assertRedirect(route('admin.settings.edit'));

        $settings = Setting::current();
        $this->assertTrue($settings->hasCommonStudentPassword());
        $this->assertTrue(Hash::check('override-secret', $settings->common_student_password));
    }

    public function test_leaving_the_field_blank_keeps_the_existing_common_student_password(): void
    {
        $admin = $this->makeSuperAdmin();
        Setting::current()->update(['common_student_password' => 'first-secret']);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), ['common_student_password' => ''])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertTrue(Hash::check('first-secret', Setting::current()->common_student_password));
    }

    public function test_common_student_password_is_hidden_from_array_and_json_serialization(): void
    {
        Setting::current()->update(['common_student_password' => 'override-secret']);

        $array = Setting::current()->toArray();

        $this->assertArrayNotHasKey('common_student_password', $array);
    }

    public function test_settings_page_never_prefills_the_common_student_password_input(): void
    {
        $admin = $this->makeSuperAdmin();
        Setting::current()->update(['common_student_password' => 'override-secret']);

        $response = $this->actingAs($admin)->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertDontSee('override-secret');
    }

    /**
     * The Teacher Override Password is ONE global setting — the Settings
     * page must not offer a branch selector anywhere near it. (A separate,
     * unrelated "Default Branch for New Students" field does still exist
     * further down the page for JIT student provisioning — this only
     * pins down that the password field itself carries no branch element.)
     */
    public function test_settings_page_does_not_show_a_branch_selector_for_the_teacher_override_password(): void
    {
        $admin = $this->makeSuperAdmin();

        $response = $this->actingAs($admin)->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertSee('Teacher Override Password');

        preg_match(
            '/Teacher Override Password.*?<\/form>/s',
            $response->getContent(),
            $matches
        );

        $this->assertNotEmpty($matches, 'Could not locate the Teacher Override Password form on the settings page.');
        $this->assertStringNotContainsString('Select Branch', $matches[0]);
        $this->assertStringNotContainsString('default_teacher_override_branch_id', $matches[0]);
    }

    /**
     * The Setting model stores the password as a single row with no
     * branch_id at all, so it is not "the same value repeated per branch" —
     * there is only ever one value, used for every branch's students.
     */
    public function test_common_student_password_has_no_branch_association(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('settings', 'branch_id'));

        Setting::current()->update(['common_student_password' => 'ABC123']);

        $this->assertSame(1, Setting::query()->count());
    }

    private function makeSuperAdmin(): User
    {
        return User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'password' => Hash::make('123456'),
        ]);
    }
}
