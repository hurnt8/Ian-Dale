<?php

namespace App\Support;

use App\Models\User;

/**
 * Code agent ajouté au lien d'une vue (/vue/{slug}?a=…) pour rattacher les réponses à l'agent qui l'a partagé.
 * Format « id-signature » : la signature (HMAC de la clé de l'application) empêche de fabriquer le lien d'un autre agent.
 */
final class AgentLink
{
    public const PARAM = 'a';

    public static function code(User $user): string
    {
        return $user->id.'-'.self::signature($user->id);
    }

    /** Agent (propet actif) correspondant au code, ou null si le code est absent ou invalide. */
    public static function agent(mixed $code): ?User
    {
        if (! is_string($code) || ! preg_match('/^(\d{1,10})-([0-9a-f]{10})$/', $code, $m)) {
            return null;
        }
        if (! hash_equals(self::signature((int) $m[1]), $m[2])) {
            return null;
        }

        $user = User::find((int) $m[1]);

        return $user && $user->isPropet() && $user->is_active ? $user : null;
    }

    private static function signature(int $id): string
    {
        return substr(hash_hmac('sha256', 'vue-agent:'.$id, (string) config('app.key')), 0, 10);
    }
}
