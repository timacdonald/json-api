<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use TiMacDonald\JsonApi\Support\Fields;
use TiMacDonald\JsonApi\Support\Includes;

class TestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Includes::getInstance()->flush();

        Fields::getInstance()->flush();
    }
}
