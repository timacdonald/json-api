<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use TiMacDonald\JsonApi\ResourceIdentifier;
use TiMacDonald\JsonApi\ResourceLinkage;

class RelationshipLinkTest extends TestCase
{
    public function testItSerializes()
    {
        $link = new ResourceLinkage(new ResourceIdentifier('expected-id', 'expected-type'), ['expected' => 'link', ['expected' => 'meta']]);

        $serialized = json_encode($link);

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
