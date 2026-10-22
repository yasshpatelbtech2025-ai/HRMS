<?php

namespace App\Console\Commands;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollSetting;
use App\Models\Shift;
use App\Models\User;
use App\Services\Attendance\SmartAttendance;
use App\Services\Payroll\SalaryGenerator;
use App\Support\Activity;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * A demo company filled with made-up employees, attendance and last month's
 * salary, and a demo login that can only see that company - for showing the
 * app to people without exposing real companies' data.
 */
class SetupDemo extends Command
{
    protected $signature = 'demo:setup
                            {--email=demo@example.com : The demo login email}';

    protected $description = 'Create the demo company with sample data and a demo login limited to it';

    public const COMPANY = 'Demo Industries Pvt Ltd';

    /** Made-up staff: name, gender, department, position, monthly basic. */
    private const STAFF = [
        ['Aarav Sharma', 'male', 'Production', 'Machine Operator', 14000],
        ['Priya Nair', 'female', 'Human Resources', 'HR Executive', 22000],
        ['Rohan Mehta', 'male', 'Production', 'Shift Supervisor', 26000],
        ['Sneha Kulkarni', 'female', 'Accounts', 'Accountant', 24000],
        ['Vikram Singh', 'male', 'Production', 'Machine Operator', 14000],
        ['Ananya Iyer', 'female', 'Quality', 'Quality Inspector', 18000],
        ['Karan Joshi', 'male', 'Stores', 'Store Keeper', 16000],
        ['Meera Pillai', 'female', 'Accounts', 'Accounts Assistant', 17000],
        ['Arjun Desai', 'male', 'Maintenance', 'Electrician', 19000],
        ['Pooja Reddy', 'female', 'Quality', 'Quality Inspector', 18000],
        ['Rahul Verma', 'male', 'Production', 'Helper', 12500],
        ['Kavya Menon', 'female', 'Human Resources', 'HR Assistant', 16000],
        ['Siddharth Rao', 'male', 'Maintenance', 'Fitter', 17500],
        ['Neha Gupta', 'female', 'Production', 'Packer', 13000],
        ['Aditya Patil', 'male', 'Production', 'Machine Operator', 14000],
        ['Ishita Bose', 'female', 'Stores', 'Store Assistant', 13500],
        ['Manish Yadav', 'male', 'Production', 'Helper', 12500],
        ['Riya Chopra', 'female', 'Production', 'Packer', 13000],
        ['Nikhil Jain', 'male', 'Maintenance', 'Welder', 18000],
        ['Divya Shetty', 'female', 'Accounts', 'Billing Executive', 18500],
        ['Suresh Pawar', 'male', 'Security', 'Security Guard', 13000],
        ['Lakshmi Krishnan', 'female', 'Housekeeping', 'Housekeeping Staff', 12000],
        ['Amit Thakur', 'male', 'Production', 'Shift Supervisor', 25000],
        ['Tanvi Bhatt', 'female', 'Quality', 'Quality Manager', 32000],
    ];

    public function handle(SmartAttendance $attendance, SalaryGenerator $salary): int
    {
        $validator = Validator::make(['email' => $this->option('email')], ['email' => ['required', 'lowercase', 'email', 'max:255']]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $this->option('email')]);
        if ($user->exists && ! $user->is_demo) {
            $this->error("{$user->email} is a real login; pick another --email for the demo.");

            return self::FAILURE;
        }

        $password = $this->secret('Password for the demo login');
        $passwordCheck = Validator::make(['password' => $password], ['password' => ['required', Password::defaults()]]);
        if ($passwordCheck->fails()) {
            $this->error($passwordCheck->errors()->first());

            return self::FAILURE;
        }

        $user->forceFill([
            'name' => 'Demo Viewer', 'password' => $password, 'role' => 'user', 'is_active' => true, 'is_demo' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $company = Company::firstWhere('establishment_name', self::COMPANY);
        if ($company) {
            $this->info('The demo company already exists; its data was left as it is.');
        } else {
            $company = Activity::quietly(fn () => DB::transaction(fn () => $this->fill($attendance)));
            $month = CarbonImmutable::today()->subMonthNoOverflow()->startOfMonth();
            $salary->generate($company, $month, $user);
            $this->info("Created ".self::COMPANY." with ".count(self::STAFF)." employees and {$month->format('F Y')} salary.");
        }

        // Only the demo company, whatever it was given before.
        $user->companies()->sync([$company->id]);
        $this->info("Demo login {$user->email} can see only ".self::COMPANY.'.');

        return self::SUCCESS;
    }

    private function fill(SmartAttendance $attendance): Company
    {
        $company = Company::create([
            'establishment_name' => self::COMPANY, 'address_line_1' => 'Plot 12, MIDC Industrial Area',
            'city' => 'Pune', 'district' => 'Pune', 'state' => 'Maharashtra', 'country' => 'India',
        ]);
        PayrollSetting::create(['company_id' => $company->id, 'bonus_frequency' => 'monthly']);
        $shift = Shift::create([
            'company_id' => $company->id, 'name' => 'General Shift', 'type' => 'general', 'slot' => 1,
            'timings' => ['GS' => ['start' => '09:00', 'end' => '18:00']],
        ]);

        $departments = collect(self::STAFF)->pluck(2)->unique()->mapWithKeys(fn ($name) => [$name => $company->departments()->create(['name' => $name])]);
        $positions = collect(self::STAFF)->pluck(3)->unique()->mapWithKeys(fn ($name) => [$name => $company->positions()->create(['name' => $name, 'details' => $name, 'is_active' => true])]);

        // Two months back, so last month's attendance and salary are complete.
        $from = CarbonImmutable::today()->subMonthsNoOverflow(2)->startOfMonth();

        foreach (self::STAFF as $i => [$name, $gender, $department, $position, $basic]) {
            [$first, $last] = explode(' ', $name, 2);
            $employee = new Employee([
                'company_id' => $company->id, 'first_name' => $first, 'last_name' => $last,
                'email' => strtolower("{$first}.{$last}").'@demo.example.com', 'phone' => '98'.str_pad((string) (20000000 + $i * 137), 8, '0', STR_PAD_LEFT),
                'gender' => Gender::from($gender), 'date_of_birth' => $from->subYears(22 + $i % 20)->subDays($i * 11),
                'date_of_joining' => $from->subMonths(3 + $i * 2), 'employment_type' => EmploymentType::FullTime,
                'status' => EmployeeStatus::Active, 'pay_frequency' => 'monthly', 'daily_hour' => 8,
                'monthly_salary_calculation' => 'calendar_month', 'weekly_leave_day' => 'sunday',
                'department_id' => $departments[$department]->id, 'position_id' => $positions[$position]->id, 'shift_id' => $shift->id,
            ]);
            $employee->employee_code = sprintf('D%03d', $i + 1);
            $employee->save();

            foreach (['basic' => $basic, 'hra' => round($basic * 0.25), 'allowance' => 1500] as $element => $amount) {
                $employee->salaryElements()->create(['element' => $element, 'monthly' => $amount, 'yearly' => $amount * 12]);
            }
            $employee->salaryElements()->create(['element' => 'bonus', 'monthly' => 8.33, 'yearly' => 0]);

            for ($month = $from; $month->lte(CarbonImmutable::today()); $month = $month->addMonthNoOverflow()) {
                $attendance->fill($employee->setRelation('shift', $shift), $month->year, $month->month);
            }
        }

        return $company;
    }
}
