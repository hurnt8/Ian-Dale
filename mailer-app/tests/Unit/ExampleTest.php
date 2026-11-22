<?php

namespace Tests\Unit;

use App\Support\EmailListParser;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_parses_mixed_separators_duplicates_and_invalid(): void
    {
        $raw = "\"a@example.fr, B@example.fr\nc@example.org; a@example.fr pas-un-email, d@example.com,\"";

        $r = EmailListParser::parse($raw);

        $this->assertSame(['a@example.fr', 'b@example.fr', 'c@example.org', 'd@example.com'], $r['valid']);
        $this->assertSame(['pas-un-email'], $r['invalid']);
        $this->assertSame(1, $r['duplicates']);
    }
}
