<?php

namespace App\Enums;

enum RecipientStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped'; // adresse désinscrite
    case Invalid = 'invalid'; // vérification échouée / boîte inexistante

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Sent => 'Envoyé',
            self::Failed => 'Échec',
            self::Skipped => 'Désinscrit',
            self::Invalid => 'Adresse invalide',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => '#b45309',
            self::Sent => '#047857',
            self::Failed => '#b91c1c',
            self::Skipped => '#6b7280',
            self::Invalid => '#9f1239',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'bi-hourglass-split',
            self::Sent => 'bi-check2-circle',
            self::Failed => 'bi-x-octagon',
            self::Skipped => 'bi-slash-circle',
            self::Invalid => 'bi-envelope-x',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Sent => 'green',
            self::Failed, self::Invalid => 'red',
            self::Skipped => 'gray',
        };
    }
}
