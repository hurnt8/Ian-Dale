<?php

namespace Tests;

use App\Models\Setting;
use App\Services\Verification\DomainResolver;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\FakeDomainResolver;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Jamais de requête DNS réelle en test
        $this->app->singleton(DomainResolver::class, FakeDomainResolver::class);

        // En production le filtre « Orange uniquement » est actif par défaut ;
        // les tests génériques utilisent des adresses example.fr, donc on le désactive sauf test dédié.
        if (app()->runningUnitTests()) {
            Setting::put('orange_only', '0');
        }
    }
}
