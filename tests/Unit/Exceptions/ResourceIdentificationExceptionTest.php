<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use TiMacDonald\JsonApi\Exceptions\ResourceIdentificationException;

class ResourceIdentificationExceptionTest extends TestCase
{
    public function test_it_handles_scalars_for_id(): void
    {
        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object id for [string].');

        throw ResourceIdentificationException::attemptingToDetermineIdFor('foo');
    }

    public function test_it_handles_objects_for_id(): void
    {
        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object id for [Tests\Unit\Exceptions\MyTestClass].');

        throw ResourceIdentificationException::attemptingToDetermineIdFor(new MyTestClass);
    }

    public function test_it_handles_scalars_for_type(): void
    {
        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object type for [string].');

        throw ResourceIdentificationException::attemptingToDetermineTypeFor('foo');
    }

    public function test_it_handles_objects_for_type(): void
    {
        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object type for [Tests\Unit\Exceptions\MyTestClass].');

        throw ResourceIdentificationException::attemptingToDetermineTypeFor(new MyTestClass);
    }
}

class MyTestClass
{
    //
}
