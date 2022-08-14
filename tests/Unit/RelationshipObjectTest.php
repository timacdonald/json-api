<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use TiMacDonald\JsonApi\ResourceIdentifier;
use TiMacDonald\JsonApi\RelationshipObject;

class RelationshipObjectTest extends TestCase
{
    public function testItSerializes()
    {
        $link = RelationshipObject::toOne(new ResourceIdentifier('expected-type', 'expected-id'), ['expected' => 'link'], ['expected' => 'meta']);

        $serialized = json_encode($link);

        $this->assertSame('{"data":{"type":"expected-type","id":"expected-id","meta":{}},"meta":{"expected":"meta"},"links":{"expected":{"href":"link","meta":{}}}}', $serialized);
    }

    public function testEmptyMetaAndLinksIsObject()
    {
        $link = RelationshipObject::toOne(new ResourceIdentifier('expected-type', 'expected-id'), [], []);

        $serialized = json_encode($link);

        $this->assertSame('{"data":{"type":"expected-type","id":"expected-id","meta":{}},"meta":{},"links":{}}', $serialized);
    }

    public function testMissingMetaAndLinksIsObject()
    {
        $link = RelationshipObject::toOne(new ResourceIdentifier('expected-type', 'expected-id'));

        $serialized = json_encode($link);

        $this->assertSame('{"data":{"type":"expected-type","id":"expected-id","meta":{}},"meta":{},"links":{}}', $serialized);
    }

    public function testMetaCanBeAppended()
    {
        $link = RelationshipObject::toMany([new ResourceIdentifier('expected-type', 'expected-id')], [], ["original" => "meta"]);

        $serialized = json_encode(
            $link->withMeta(['expected' => 'meta'])->withMeta(['another' => 'one'])
        );

        $this->assertSame('{"data":[{"type":"expected-type","id":"expected-id","meta":{}}],"meta":{"original":"meta","expected":"meta","another":"one"},"links":{}}', $serialized);
    }
}
