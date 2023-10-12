<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use stdClass;
use TiMacDonald\JsonApi\Exceptions\UnknownRelationshipException;

class UnknownRelationshipExceptionTest extends TestCase
{
    public function testFromForAString()
    {
        $this->expectExceptionMessage('Unknown relationship encountered. Relationships should always return a class that extends TiMacDonald\JsonApi\JsonApiResource or TiMacDonald\JsonApi\JsonApiResourceCollection. Instead found [string].');

        throw UnknownRelationshipException::from('');
    }

    public function testFromForAArray()
    {
        $this->expectExceptionMessage('Unknown relationship encountered. Relationships should always return a class that extends TiMacDonald\JsonApi\JsonApiResource or TiMacDonald\JsonApi\JsonApiResourceCollection. Instead found [array].');

        throw UnknownRelationshipException::from([]);
    }

    public function testFromForAObject()
    {
        $this->expectExceptionMessage('Unknown relationship encountered. Relationships should always return a class that extends TiMacDonald\JsonApi\JsonApiResource or TiMacDonald\JsonApi\JsonApiResourceCollection. Instead found [stdClass].');

        throw UnknownRelationshipException::from(new stdClass());
    }
}
