<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use TiMacDonald\JsonApi\ResourceIdentifier;

class ResourceIdentifierTest extends TestCase
{
    public function testItSerializes()
    {
        $identifier = new ResourceIdentifier('expected-id', 'expected-type', ['expected' => 'meta']);

        $serialized = json_encode($identifier);

        $this->assertSame('{"id":"expected-id","type":"expected-type","meta":{"expected":"meta"}}', $serialized);
    }

    public function testEmptyMetaIsObject()
    {
        $identifier = new ResourceIdentifier('expected-id', 'expected-type', []);

        $serialized = json_encode($identifier);

        $this->assertSame('{"id":"expected-id","type":"expected-type","meta":{}}', $serialized);
    }

    public function testMissingMetaIsObject()
    {
        $identifier = new ResourceIdentifier('expected-id', 'expected-type');

        $serialized = json_encode($identifier);

        $this->assertSame('{"id":"expected-id","type":"expected-type","meta":{}}', $serialized);
    }

    public function testMetaCanBeAppended()
    {
        $identifier = (new ResourceIdentifier('expected-id', 'expected-type'));

        $serialized = json_encode($identifier->withMeta(['expected' => 'meta']));

        $this->assertSame('{"id":"expected-id","type":"expected-type","meta":{"expected":"meta"}}', $serialized);
    }
}
