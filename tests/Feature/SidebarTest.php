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

    public function test_every_section_tab_loads_and_shows_its_sibling_tabs(): void
    {
        $user = User::factory()->create();

        foreach (config('navigation') as $section => $tabs) {
            foreach (array_keys($tabs) as $key) {
                $response = $this->actingAs($user)->get(route($section.'.'.($key === '' ? 'index' : $key)))->assertOk();

                if (count($tabs) > 1) {
                    $response->assertSeeInOrder(array_values($tabs));
                }
            }
        }
    }

    public function test_section_pages_require_login(): void
    {
        $this->get('/company')->assertRedirect('/login');
        $this->get('/payroll/loans')->assertRedirect('/login');
    }
}
