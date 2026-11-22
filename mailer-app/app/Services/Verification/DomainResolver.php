<?php

namespace App\Services\Verification;

use Illuminate\Support\Facades\Cache;

/**
 * Requêtes DNS (mises en cache 24 h par domaine).
 * Isolé dans une classe pour pouvoir être simulé dans les tests.
 */
class DomainResolver
{
    public function hasMx(string $domain): bool
    {
        return $this->lookup($domain, 'MX');
    }

    public function hasAddress(string $domain): bool
    {
        return $this->lookup($domain, 'A') || $this->lookup($domain, 'AAAA');
    }

    private function lookup(string $domain, string $type): bool
    {
        $ascii = function_exists('idn_to_ascii') ? (idn_to_ascii($domain) ?: $domain) : $domain;

        return Cache::remember("dns.$type.$ascii", now()->addDay(), function () use ($ascii, $type): bool {
            // Le point final évite que le résolveur ajoute un suffixe de recherche local.
            return @checkdnsrr($ascii.'.', $type);
        });
    }
}
