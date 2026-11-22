<?php

namespace Tests\Support;

use App\Services\Verification\DomainResolver;

/** Résolveur DNS simulé : aucun appel réseau pendant les tests. */
class FakeDomainResolver extends DomainResolver
{
    /** Domaines avec serveur mail */
    public array $mx = ['example.fr', 'example.com', 'example.org', 'gmail.com', 'orange.fr', 'wanadoo.fr', 'e.fr'];

    /** Domaines existants sans MX */
    public array $a = ['site-sans-mail.fr'];

    public function hasMx(string $domain): bool
    {
        return in_array($domain, $this->mx, true);
    }

    public function hasAddress(string $domain): bool
    {
        return in_array($domain, $this->mx, true) || in_array($domain, $this->a, true);
    }
}
