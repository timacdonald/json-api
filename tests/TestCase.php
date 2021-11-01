<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Once\Cache;

class TestCase extends BaseTestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        Cache::getInstance()->flush();
    }
}
