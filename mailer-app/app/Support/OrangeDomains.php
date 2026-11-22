<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Restriction « destinataires Orange uniquement ».
 * Les domaines autorisés sont paramétrables (Admin > Paramètres) ; par défaut les webmails grand public d'Orange.
 */
final class OrangeDomains
{
    /** Domaines Orange par défaut (France). */
    public const DEFAULT = ['orange.fr', 'wanadoo.fr'];

    /** @return list<string> domaines autorisés, en minuscules */
    public static function list(): array
    {
        $raw = (string) Setting::get('orange_domains');
        $domains = array_filter(array_map(
            fn ($d) => ltrim(mb_strtolower(trim($d)), '@'),
            preg_split('/[\s,;]+/', $raw) ?: []
        ));

        return array_values(array_unique($domains ?: self::DEFAULT));
    }

    /** La restriction est-elle active ? */
    public static function enforced(): bool
    {
        return Setting::bool('orange_only');
    }

    /** L'adresse appartient-elle à un domaine Orange autorisé ? */
    public static function allows(string $email): bool
    {
        $at = mb_strrpos($email, '@');
        if ($at === false) {
            return false;
        }

        return in_array(mb_strtolower(mb_substr($email, $at + 1)), self::list(), true);
    }
}
