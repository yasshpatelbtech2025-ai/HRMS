<?php

namespace App\Enums;

enum EmploymentType: string
{
    case Intern = 'intern';
    case Contractual = 'contractual';
    case FullTime = 'full_time';
    case Remote = 'remote';

    public function label(): string
    {
        return match ($this) {
            self::Intern => 'Intern',
            self::Contractual => 'Contractual',
            self::FullTime => 'Full Time',
            self::Remote => 'Remote',
        };
    }
}
