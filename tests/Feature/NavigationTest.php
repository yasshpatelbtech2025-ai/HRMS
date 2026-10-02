<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    private const SECTIONS = [
        'company.index', 'attendance.index', 'employees.index', 'compliance.index',
        'documents.index', 'payroll.index', 'reports.index', 'configs.index',
    ];

    public function test_sidebar_links_to_every_section(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk();

        foreach (self::SECTIONS as $route) {
            $response->assertSee(route($route), false);
        }
    }

    public function test_sections_require_login_and_render_for_staff(): void
    {
        foreach (self::SECTIONS as $route) {
            $this->get(route($route))->assertRedirect('/login');
        }

        $this->actingAs(User::factory()->create());

        foreach (self::SECTIONS as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_dashboard_shows_summary_cards(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Total employees')
            ->assertSeeText('Total compliances')
            ->assertSeeText('Overdue');
    }
}
