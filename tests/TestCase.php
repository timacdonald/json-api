<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Once\Cache;

class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        parent::tearDown();

        Cache::getInstance()->flush();
    }
}
