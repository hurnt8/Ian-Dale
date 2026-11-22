<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Propet = 'propet';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Propet => 'Propet',
        };
    }
}
