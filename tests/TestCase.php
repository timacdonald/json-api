<?php

declare(strict_types=1);

namespace Tests;

use TiMacDonald\JsonApi\JsonApiServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return array<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            JsonApiServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }
}
