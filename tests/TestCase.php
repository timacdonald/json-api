<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Once\Cache;
use TiMacDonald\JsonApi\Testing\MakesJsonApiRequests;

class TestCase extends BaseTestCase
{
    use MakesJsonApiRequests;

    public function tearDown(): void
    {
        parent::tearDown();

        Cache::getInstance()->flush();
    }
}
