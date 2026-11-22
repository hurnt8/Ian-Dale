<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case Pending = 'pending';     // en attente de validation admin
    case Sending = 'sending';     // envoi en cours (file d'attente)
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente de validation',
            self::Sending => 'Envoi en cours',
            self::Completed => 'Terminée',
            self::Rejected => 'Refusée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => '#b45309',
            self::Sending => '#1d4ed8',
            self::Completed => '#047857',
            self::Rejected => '#b91c1c',
        };
    }
}
