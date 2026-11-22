<?php

namespace App\Services\Verification;

use App\Enums\VerificationStatus;

/**
 * Vérifie qu'une adresse est correcte et que son domaine peut recevoir des emails :
 *  1. syntaxe (RFC)
 *  2. adresse jetable
 *  3. domaine existant + serveur mail (enregistrement MX)
 *  4. faute de frappe probable sur les fournisseurs courants
 *
 * L'existence de la boîte elle-même n'est pas sondée (les serveurs Orange, Gmail…
 * bloquent ou faussent ce type de test). Elle est connue au premier envoi :
 * un rejet définitif (550 / 5.1.1) place l'adresse en liste de suppression.
 */
class EmailVerifier
{
    /** Domaines courants pour la détection de fautes de frappe. */
    private const KNOWN_DOMAINS = [
        'gmail.com', 'orange.fr', 'wanadoo.fr', 'free.fr', 'sfr.fr', 'neuf.fr', 'laposte.net',
        'hotmail.fr', 'hotmail.com', 'outlook.fr', 'outlook.com', 'live.fr', 'msn.com',
        'yahoo.fr', 'yahoo.com', 'icloud.com', 'me.com', 'aol.com', 'bbox.fr', 'gmx.fr', 'protonmail.com',
    ];

    private const DISPOSABLE_DOMAINS = [
        'yopmail.com', 'yopmail.fr', 'mailinator.com', 'guerrillamail.com', '10minutemail.com',
        'tempmail.com', 'temp-mail.org', 'trashmail.com', 'jetable.org', 'getnada.com',
        'sharklasers.com', 'maildrop.cc', 'dispostable.com', 'throwawaymail.com',
    ];

    public function __construct(private DomainResolver $dns) {}

    public function verify(string $email): VerificationResult
    {
        $email = mb_strtolower(trim($email));

        if (mb_strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return new VerificationResult($email, VerificationStatus::InvalidSyntax, "Format d'adresse incorrect.");
        }

        [$local, $domain] = explode('@', $email, 2);

        if (mb_strlen($local) > 64) {
            return new VerificationResult($email, VerificationStatus::InvalidSyntax, 'Partie avant le @ trop longue.');
        }

        if (in_array($domain, self::DISPOSABLE_DOMAINS, true)) {
            return new VerificationResult($email, VerificationStatus::Disposable, "Domaine d'adresses temporaires.");
        }

        $hasMx = $this->dns->hasMx($domain);
        $suggestion = $this->suggestDomain($domain, $hasMx);

        if ($suggestion !== null) {
            return new VerificationResult($email, VerificationStatus::Typo, "Vouliez-vous dire $local@$suggestion ?", "$local@$suggestion");
        }

        if ($hasMx) {
            return new VerificationResult($email, VerificationStatus::Valid, 'Domaine joignable (serveur mail trouvé).');
        }

        if ($this->dns->hasAddress($domain)) {
            return new VerificationResult($email, VerificationStatus::Risky, 'Le domaine existe mais ne déclare pas de serveur mail.');
        }

        return new VerificationResult($email, VerificationStatus::DomainNotFound, "Le domaine « $domain » n'existe pas.");
    }

    /**
     * @param  iterable<string>  $emails
     * @return list<VerificationResult>
     */
    public function verifyMany(iterable $emails): array
    {
        $out = [];
        foreach ($emails as $email) {
            $out[] = $this->verify($email);
        }

        return $out;
    }

    private function suggestDomain(string $domain, bool $hasMx): ?string
    {
        if (in_array($domain, self::KNOWN_DOMAINS, true)) {
            return null;
        }

        $best = null;
        $bestDistance = PHP_INT_MAX;

        foreach (self::KNOWN_DOMAINS as $known) {
            $d = levenshtein($domain, $known);
            if ($d < $bestDistance) {
                [$best, $bestDistance] = [$known, $d];
            }
        }

        // Distance 1 (gmial.com, orange.fe…) : suspect même si le domaine répond.
        // Distance 2 : seulement si le domaine ne reçoit pas d'emails.
        return ($bestDistance === 1 || ($bestDistance === 2 && ! $hasMx)) ? $best : null;
    }
}
