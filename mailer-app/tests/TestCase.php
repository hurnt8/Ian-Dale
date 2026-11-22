<?php

namespace Tests;

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
    }
}
