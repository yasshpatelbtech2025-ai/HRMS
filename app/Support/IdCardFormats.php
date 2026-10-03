<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Firm;
use App\Services\Compliance\ComplianceForms;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * The ID card designs (Company › ID Card Formats), copied from the old
 * system: each a front and back background (public/images/id-cards) with
 * the details placed over it, kept as markup with {{tokens}} in
 * resources/id-card-formats.json.
 */
class IdCardFormats
{
    public const DEFAULT = 2;

    /** Tokens that are image addresses; their image is left out when empty. */
    private const IMAGES = ['photo', 'logo', 'holder_signature', 'authority'];

    private static ?Collection $formats = null;

    /**
     * @return Collection<int, array{id: int, orientation: string, front: string, back: string}>
     */
    public static function all(): Collection
    {
        return self::$formats ??= collect(json_decode(file_get_contents(resource_path('id-card-formats.json')), true))->keyBy('id');
    }

    public static function find(int $id): ?array
    {
        return self::all()->get($id);
    }

    /** The design chosen for the firm (the old system's default until one is picked). */
    public static function selected(?Firm $firm = null): array
    {
        $firm ??= Firm::query()->first();

        return self::find($firm?->id_card_format ?? self::DEFAULT) ?? self::find(self::DEFAULT);
    }

    /**
     * One side of a card filled in with the given values.
     *
     * @param  array<string, string|null>  $values
     */
    public static function render(array $format, string $side, array $values): HtmlString
    {
        $html = $format[$side];
        foreach (self::IMAGES as $token) {
            $html = blank($values[$token] ?? null)
                ? preg_replace('#<img[^>]*src="\{\{'.$token.'\}\}"[^>]*>#', '', $html)
                : str_replace('{{'.$token.'}}', e($values[$token]), $html);
        }
        $html = preg_replace_callback('#\{\{(\w+)\}\}#', fn ($m) => e($values[$m[1]] ?? ''), $html);

        $landscape = $format['orientation'] === 'landscape';
        $card = $landscape ? "horizontal-{$side}-card" : "{$side}-card";
        $background = $landscape ? "horizontal-{$side}" : $side;

        return new HtmlString(sprintf(
            '<div class="%s"><div class="%s"><img src="%s" class="%s" alt="" loading="lazy">%s</div></div>',
            $landscape ? 'horizontal-identity-card' : 'identity-card', $card, asset("images/id-cards/{$format['id']}-{$side}.jpg"), $background, $html,
        ));
    }

    /**
     * The sample details the old system showed on the designs.
     *
     * @return array<string, string|null>
     */
    public static function sample(Firm $firm): array
    {
        return [
            'name' => 'Employee Name', 'designation' => 'Designation', 'father' => 'Employee Father', 'dob' => '01/01/1970', 'mobile' => '9876543210',
            'blood_group' => 'O+', 'job_type' => 'Full-Time', 'id_no' => '0000', 'issued_on' => today()->format('d/m/Y'),
            'firm' => $firm->establishment_name ?: 'Firm Name', 'firm_address' => $firm->address(),
            'present_address' => 'Present address', 'permanent_address' => 'Permanent address',
            'photo' => asset('images/id-cards/avatar.svg'), 'logo' => $firm->logo_path ? route('company.firm.image', 'logo') : null,
            'authority' => self::authority($firm), 'holder_signature' => null,
        ];
    }

    /**
     * An employee's details for their card.
     *
     * @return array<string, string|null>
     */
    public static function forEmployee(Employee $employee, Firm $firm): array
    {
        $employee->loadMissing(['company', 'position', 'familyMembers', 'addresses']);

        return [
            'name' => $employee->full_name,
            'designation' => $employee->position?->name,
            'father' => ComplianceForms::guardian($employee),
            'dob' => $employee->date_of_birth?->format('d/m/Y'),
            'mobile' => $employee->phone,
            'blood_group' => $employee->blood_group,
            'job_type' => $employee->employment_type?->label(),
            'id_no' => $employee->employee_code,
            'issued_on' => ($employee->id_card_assigned_on ?? today())->format('d/m/Y'),
            'firm' => $firm->establishment_name ?: $employee->company?->establishment_name,
            'firm_address' => $firm->exists ? $firm->address() : '',
            'present_address' => ComplianceForms::address($employee->address('present') ?? $employee->address('permanent')),
            'permanent_address' => ComplianceForms::address($employee->address('permanent')),
            'photo' => $employee->photo_path ? route('employees.file', [$employee, 'photo']) : asset('images/id-cards/avatar.svg'),
            'logo' => $firm->logo_path ? route('company.firm.image', 'logo') : null,
            'authority' => self::authority($firm),
            'holder_signature' => null,
        ];
    }

    /** The issuing authority's stamp and signature (or either). */
    private static function authority(Firm $firm): ?string
    {
        foreach (['stamp_signature' => 'stamp_signature_path', 'signature' => 'signature_path', 'stamp' => 'stamp_path'] as $image => $column) {
            if ($firm->{$column}) {
                return route('company.firm.image', $image);
            }
        }

        return null;
    }
}
