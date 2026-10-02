<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Transgender = 'transgender';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
