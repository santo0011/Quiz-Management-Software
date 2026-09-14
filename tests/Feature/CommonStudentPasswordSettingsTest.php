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
