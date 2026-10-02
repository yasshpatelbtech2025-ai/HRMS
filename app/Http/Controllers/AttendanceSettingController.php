<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use App\Models\Company;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Configs › Attendance: a company's punch buffer and its shifts (Open,
 * General 1–4, Day & Night, Rotational 1–4), each with timings and pay rules.
 * A shift slot is in use when it has a title.
 */
class AttendanceSettingController extends Controller
{
    public function index(Request $request): View
    {
        $companies = Company::with('parent')->orderBy('establishment_name')->get();
        $company = $companies->firstWhere('uuid', $request->query('company')) ?? $companies->first();

        return view('configs.attendance', [
            'companies' => $companies,
            'company' => $company,
            'setting' => $company ? AttendanceSetting::firstOrNew(['company_id' => $company->id]) : null,
            'shifts' => $company ? $company->shifts()->withCount('employees')->get()->keyBy(fn (Shift $s) => "{$s->type}.{$s->slot}") : collect(),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $existing = $company->shifts()->withCount('employees')->get()->keyBy(fn (Shift $s) => "{$s->type}.{$s->slot}");
        $validator = Validator::make($request->all(), $this->rules(), [], $this->attributes());

        $validator->after(function ($validator) use ($request, $existing) {
            $names = [];

            foreach ($this->slots() as [$type, $slot, $parts]) {
                $input = $request->input("shifts.{$type}.{$slot}", []);
                $name = trim((string) ($input['name'] ?? ''));
                $shift = $existing->get("{$type}.{$slot}");

                if ($name === '') {
                    if ($shift?->employees_count) {
                        $validator->errors()->add("shifts.{$type}.{$slot}.name", __(':shift is assigned to :count employee(s), so it needs a title. Move them to another shift first.', [
                            'shift' => $shift->name, 'count' => $shift->employees_count,
                        ]));
                    }

                    continue;
                }

                if (isset($names[mb_strtolower($name)])) {
                    $validator->errors()->add("shifts.{$type}.{$slot}.name", __('Another shift is already called ":name".', ['name' => $name]));
                }
                $names[mb_strtolower($name)] = true;

                foreach (array_keys($parts) as $code) {
                    foreach (['start', 'end'] as $edge) {
                        if (blank($input['timings'][$code][$edge] ?? null)) {
                            $validator->errors()->add("shifts.{$type}.{$slot}.timings.{$code}.{$edge}", __('Enter the :edge time.', ['edge' => $edge]));
                        }
                    }
                }
            }
        });

        $data = $validator->validate();

        DB::transaction(function () use ($company, $data, $existing) {
            AttendanceSetting::updateOrCreate(['company_id' => $company->id], ['punch_buffer_minutes' => $data['punch_buffer_minutes'] ?? 0]);

            foreach ($this->slots() as [$type, $slot, $parts]) {
                $input = $data['shifts'][$type][$slot] ?? [];
                $name = trim((string) ($input['name'] ?? ''));
                $shift = $existing->get("{$type}.{$slot}");

                if ($name === '') {
                    $shift?->delete();

                    continue;
                }

                $timings = collect(array_keys($parts))->mapWithKeys(fn ($code) => [$code => [
                    'start' => substr($input['timings'][$code]['start'], 0, 5),
                    'end' => substr($input['timings'][$code]['end'], 0, 5),
                ]])->all();

                $rules = collect(Shift::RULE_DEFAULTS)->map(fn ($default, $field) => array_key_exists($field, $input) ? $input[$field] : $default)->all();
                $rules['pay_daily_rate_on_wo_ph'] = (bool) $rules['pay_daily_rate_on_wo_ph'];

                Shift::updateOrCreate(
                    ['company_id' => $company->id, 'type' => $type, 'slot' => $slot],
                    ['name' => $name, 'timings' => $timings] + $rules,
                );
            }
        });

        return redirect()->route('configs.index', ['company' => $company->uuid])->with('status', __('Attendance settings for :company saved.', ['company' => $company->establishment_name]));
    }

    /**
     * Every shift slot a company can configure.
     *
     * @return list<array{string, int, array<string, ?string>}> [type, slot, parts]
     */
    private function slots(): array
    {
        $slots = [];

        foreach (config('attendance.shift_types') as $type => $definition) {
            for ($slot = 1; $slot <= $definition['slots']; $slot++) {
                $slots[] = [$type, $slot, $definition['parts']];
            }
        }

        return $slots;
    }

    private function rules(): array
    {
        $choice = fn (string $list) => ['nullable', Rule::in(array_keys(config("attendance.{$list}")))];

        $rules = [
            'punch_buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'shifts' => ['nullable', 'array'],
        ];

        foreach ($this->slots() as [$type, $slot, $parts]) {
            $prefix = "shifts.{$type}.{$slot}";
            $rules += [
                "{$prefix}.name" => ['nullable', 'string', 'max:255'],
                "{$prefix}.half_day_rule" => $choice('half_day_rules'),
                "{$prefix}.wo_ph_work_pay" => $choice('wo_ph_work_pay'),
                "{$prefix}.pay_daily_rate_on_wo_ph" => ['nullable', 'boolean'],
                "{$prefix}.ot_effect_on" => $choice('effect_on'),
                "{$prefix}.ot_rate" => $choice('ot_rates'),
                "{$prefix}.ph_ot_effect_on" => $choice('effect_on'),
                "{$prefix}.ph_ot_rate" => $choice('ot_rates'),
                "{$prefix}.ot_after_hours" => ['nullable', 'numeric', 'min:0', 'max:24'],
                "{$prefix}.late_penalty_after_minutes" => ['nullable', 'integer', 'min:0', 'max:600'],
                "{$prefix}.pf_effect_on" => $choice('effect_on'),
                "{$prefix}.esi_effect_on" => $choice('effect_on'),
            ];

            foreach (array_keys($parts) as $code) {
                $rules["{$prefix}.timings.{$code}.start"] = ['nullable', 'date_format:H:i,H:i:s'];
                $rules["{$prefix}.timings.{$code}.end"] = ['nullable', 'date_format:H:i,H:i:s'];
            }
        }

        return $rules;
    }

    private function attributes(): array
    {
        return [
            'punch_buffer_minutes' => 'punch buffer time',
            'shifts.*.*.name' => 'title',
            'shifts.*.*.ot_after_hours' => 'overtime hours',
            'shifts.*.*.late_penalty_after_minutes' => 'penalty minutes',
            'shifts.*.*.timings.*.start' => 'start time',
            'shifts.*.*.timings.*.end' => 'end time',
        ];
    }
}
