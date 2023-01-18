<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use TiMacDonald\JsonApi\Exceptions\ResourceIdentificationException;

class ResourceIdentificationExceptionTest extends TestCase
{
    public function testItHandlesScalarsForId(): void
    {
        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object id for [string].');

        throw ResourceIdentificationException::attemptingToDetermineIdFor('foo');
    }

    public function testItHandlesObjectsForId(): void
    {
        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object id for [Tests\Unit\MyTestClass].');

        throw ResourceIdentificationException::attemptingToDetermineIdFor(new MyTestClass());
    }

    public function testItHandlesScalarsForType(): void
    {
        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object type for [string].');

        throw ResourceIdentificationException::attemptingToDetermineTypeFor('foo');
    }

    public function testItHandlesObjectsForType(): void
    {
        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object type for [Tests\Unit\MyTestClass].');

        throw ResourceIdentificationException::attemptingToDetermineTypeFor(new MyTestClass());
    }
}

class MyTestClass
{
    //
}
