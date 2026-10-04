<?php

namespace Tests\Feature;

use App\Jobs\ImportEmployees;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeImportRun;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\ResetPasswordQueued;
use App\Services\Attendance\SmartAttendance;
use App\Services\Payroll\SalaryGenerator;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReliabilityTest extends TestCase
{
    use RefreshDatabase;

    /** Employees with a salary and a month of attendance, in a new company. */
    private function staff(int $count): Company
    {
        $company = Company::factory()->create();
        $shift = Shift::factory()->for($company)->create(['timings' => ['GS' => ['start' => '09:00', 'end' => '18:00']]]);
        Employee::factory()->count($count)->create([
            'company_id' => $company->id, 'shift_id' => $shift->id, 'date_of_joining' => '2026-01-01', 'weekly_leave_day' => 'sunday',
            'pay_frequency' => 'monthly', 'monthly_salary_calculation' => 'calendar_month',
        ])->each(function (Employee $employee) {
            $employee->salaryElements()->create(['element' => 'basic', 'monthly' => 15000, 'yearly' => 180000]);
            app(SmartAttendance::class)->fill($employee->fresh('shift'), 2026, 9);
        });

        return $company;
    }

    private function queries(callable $callback): int
    {
        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });
        $callback();

        return $count;
    }

    public function test_lazy_loading_is_refused_outside_production(): void
    {
        $this->assertTrue(Model::preventsLazyLoading());
    }

    public function test_salary_generation_and_list_pages_do_not_grow_queries_per_employee(): void
    {
        Carbon::setTestNow('2026-10-05 12:00:00');
        $user = User::factory()->create();
        $this->actingAs($user);
        $small = $this->staff(2);
        $large = $this->staff(8);
        $generate = fn (Company $c) => $this->queries(fn () => app(SalaryGenerator::class)->generate($c, CarbonImmutable::parse('2026-09-01'), $user));

        $this->assertSame($generate($small), $generate($large));

        foreach (['attendance.index', 'payroll.tds'] as $page) {
            $open = fn (Company $c) => $this->queries(fn () => $this->get(route($page, ['company' => $c->uuid, 'month' => '2026-09']))->assertOk());
            $this->assertSame($open($small), $open($large), "{$page} runs more queries for more employees");
        }
    }

    public function test_employee_imports_run_in_the_background(): void
    {
        Queue::fake();
        Storage::fake('local');
        $company = Company::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->post(route('employees.import'), ['company_id' => $company->id, 'file' => UploadedFile::fake()->createWithContent('staff.csv', "First Name *\nRavi")])
            ->assertSessionHas('status');

        $run = EmployeeImportRun::sole();
        $this->assertSame('queued', $run->status);
        Storage::disk('local')->assertExists($run->path);
        Queue::assertPushed(ImportEmployees::class, fn (ImportEmployees $job) => $job->run->is($run));
        $this->get(route('employees.index'))->assertSee('Importing staff.csv');
    }

    public function test_someone_elses_import_status_is_not_shown(): void
    {
        $company = Company::factory()->create();
        $run = EmployeeImportRun::create(['company_id' => $company->id, 'user_id' => User::factory()->create()->id, 'original_name' => 'a.csv']);

        $this->actingAs(User::factory()->user([$company])->create())
            ->getJson(route('employees.import.status', $run))->assertNotFound();
    }

    public function test_password_reset_email_is_queued(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordQueued::class, fn ($notification) => $notification instanceof ShouldQueue);
    }

    public function test_error_pages_are_branded_and_never_show_debug_details(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/__boom', fn () => throw new \RuntimeException('secret internals'));
        $this->actingAs(User::factory()->create());

        $this->get('/__boom')->assertStatus(500)->assertSee('Something went wrong')->assertSee('SRYMPEX')->assertDontSee('secret internals');
        $this->get('/no-such-page')->assertNotFound()->assertSee('Page not found')->assertSee('Go to dashboard');

        $user = User::factory()->user([Company::factory()->create()])->create();
        $this->actingAs($user)->get(route('company.firm'))->assertForbidden()->assertSee("You don't have access to this page");
    }

    public function test_debug_mode_is_forced_off_in_production(): void
    {
        $config = fn (string $env, string $debug) => (function () use ($env, $debug) {
            putenv("APP_ENV={$env}");
            putenv("APP_DEBUG={$debug}");
            $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = $env;
            $_ENV['APP_DEBUG'] = $_SERVER['APP_DEBUG'] = $debug;

            return (require base_path('config/app.php'))['debug'];
        })();

        try {
            $this->assertFalse($config('production', 'true'));
            $this->assertTrue($config('local', 'true'));
        } finally {
            $config('testing', 'false');
        }
    }
}
