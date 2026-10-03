<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * The contractor firm running this HRMS (Company › Firm): one row.
 */
#[Fillable([
    'establishment_name', 'sector', 'organization_type', 'establishment_category', 'premise_no', 'pin_code', 'registered_on', 'commenced_on',
    'registration_no', 'is_factory_registered', 'pan', 'address_line_1', 'address_line_2', 'village', 'state', 'district', 'taluka',
    'nearest_police_station', 'owners', 'factory_manager', 'mail_protocol', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_type',
])]
class Firm extends Model
{
    public const SECTORS = [
        'Central Govt. Controlled', 'Central Govt. Department', 'Central Public Sector Undertaking', 'Co-operative Society', 'Firms Run By Trust', 'HUF',
        'Limited Liability Partnership', 'Local Bodies / Municipal Corporations', 'Partnership Firms', 'Private Ltd Company', 'Proprietory Firms',
        'Public Ltd Company', 'Registered Society', 'State Govt. Controlled', 'State Govt. Department', 'State Public Sector Undertaking',
    ];

    public const ORGANIZATION_TYPES = ['Proprietorship', 'Partnership Firm', 'Partnership LLP', 'Private Ltd.', 'Public Ltd.', 'Cooperative', 'Government', 'Semi Government', 'Other'];

    public const CATEGORIES = [
        'Commercial Establishment', 'Eating-house', 'Factory', 'Mine', 'Other places of public amusement or entertainment', 'Residential Hotel',
        'Restaurant', 'Shop', 'Theatre', 'Others',
    ];

    /** The fields kept for each owner and the factory manager. */
    public const PERSON_FIELDS = [
        'first_name' => 'First Name', 'middle_name' => 'Middle Name', 'last_name' => 'Last Name', 'dob' => 'Date Of Birth', 'designation' => 'Designation',
        'premise_no' => 'Premise No.', 'gender' => 'Gender', 'position_date' => 'Date Of Position', 'pan' => 'PAN', 'address_line_1' => 'Address Line 1',
        'address_line_2' => 'Address Line 2', 'village' => 'Village', 'state' => 'State', 'district' => 'District', 'taluka' => 'Taluka',
        'pin_code' => 'PIN Code', 'mobile' => 'Mobile', 'email' => 'Email',
    ];

    protected function casts(): array
    {
        return [
            'registered_on' => 'date', 'commenced_on' => 'date', 'is_factory_registered' => 'boolean', 'mail_port' => 'integer',
            'pan' => 'encrypted', 'owners' => 'encrypted:array', 'factory_manager' => 'encrypted:array', 'mail_password' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('firm-mail'));
    }

    /** The one firm (unsaved and empty until it is first filled in). */
    public static function current(): self
    {
        return static::query()->first() ?? new static(['establishment_name' => '']);
    }

    public function address(): string
    {
        return collect([$this->premise_no, $this->address_line_1, $this->address_line_2, $this->village, $this->taluka, $this->district, $this->state, $this->pin_code])->filter()->implode(', ');
    }

    /**
     * The firm's SMTP settings for Laravel's mailer, or null when not set.
     *
     * @return array<string, mixed>|null
     */
    public function mailConfig(): ?array
    {
        if (! $this->mail_host || ! $this->mail_username) {
            return null;
        }

        return [
            'transport' => 'smtp',
            'scheme' => $this->mail_port === 465 ? 'smtps' : 'smtp',
            'host' => $this->mail_host,
            'port' => $this->mail_port ?: 587,
            'username' => $this->mail_username,
            'password' => $this->mail_password,
        ];
    }
}
