<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case Active = 'active';
    case OnNotice = 'on_notice';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::OnNotice => 'On notice',
            self::Inactive => 'Inactive',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Active => 'bg-teal-100 text-teal-700',
            self::OnNotice => 'bg-amber-100 text-amber-700',
            self::Inactive => 'bg-slate-200 text-slate-700',
        };
    }
}
