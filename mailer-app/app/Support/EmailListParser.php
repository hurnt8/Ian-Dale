<?php

namespace App\Support;

/**
 * Transforme un texte libre ("a@x.fr, b@y.fr\nc@z.fr; ...") en liste d'adresses propres.
 */
final class EmailListParser
{
    /**
     * @return array{valid: list<string>, invalid: list<string>, duplicates: int}
     */
    public static function parse(string $raw): array
    {
        $tokens = preg_split('/[\s,;]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $valid = [];
        $invalid = [];
        $duplicates = 0;

        foreach ($tokens as $token) {
            // retire guillemets, chevrons et ponctuation autour de l'adresse
            $email = mb_strtolower(trim($token, " \t\n\r\0\x0B\"'<>()[]."));

            if ($email === '') {
                continue;
            }

            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $invalid[] = $token;
                continue;
            }

            if (isset($valid[$email])) {
                $duplicates++;
                continue;
            }

            $valid[$email] = true;
        }

        return [
            'valid' => array_keys($valid),
            'invalid' => array_values(array_unique($invalid)),
            'duplicates' => $duplicates,
        ];
    }
}
