<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_sidebar_items_in_order_and_stat_cards(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/dashboard');

        $response->assertOk()->assertSeeInOrder([
            'Dashboard', 'Company', 'Attendance', 'Employee', 'Compliance',
            'Documents', 'Payroll', 'Reports', 'Configs',
        ]);
        $response->assertSee('Total employee')->assertSee('Today presents')
            ->assertSee('Today absents')->assertSee('Today leave');
    }

    public function test_module_pages_load_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        foreach (['company', 'attendance', 'employees', 'compliance', 'documents', 'payroll', 'reports', 'configs'] as $uri) {
            $this->actingAs($user)->get("/{$uri}")->assertOk();
        }
    }

    public function test_module_pages_require_login(): void
    {
        $this->get('/company')->assertRedirect('/login');
    }
}
