<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use TiMacDonald\JsonApi\Link;
use TiMacDonald\JsonApi\ResourceIdentifier;
use TiMacDonald\JsonApi\RelationshipObject;

class RelationshipObjectTest extends TestCase
{
    public function testItSerializes(): void
    {
        $link = RelationshipObject::toOne(new ResourceIdentifier('expected-type', 'expected-id'), [new Link('expected', 'link')], ['expected' => 'meta']);

        $serialized = json_encode($link);

        $this->assertSame('{"data":{"type":"expected-type","id":"expected-id","meta":{}},"meta":{"expected":"meta"},"links":{"expected":{"href":"link","meta":{}}}}', $serialized);
    }

    public function testEmptyMetaAndLinksIsObject(): void
    {
        $link = RelationshipObject::toOne(new ResourceIdentifier('expected-type', 'expected-id'), [], []);

        $serialized = json_encode($link);

        $this->assertSame('{"data":{"type":"expected-type","id":"expected-id","meta":{}},"meta":{},"links":{}}', $serialized);
    }

    public function testMissingMetaAndLinksIsObject(): void
    {
        $link = RelationshipObject::toOne(new ResourceIdentifier('expected-type', 'expected-id'));

        $serialized = json_encode($link);

        $this->assertSame('{"data":{"type":"expected-type","id":"expected-id","meta":{}},"meta":{},"links":{}}', $serialized);
    }

    public function testMetaAndLinksCanBeAppended(): void
    {
        $link = RelationshipObject::toMany([new ResourceIdentifier('expected-type', 'expected-id')], [Link::related('related.com')], ["original" => "meta"]);

        $serialized = json_encode(
            $link->withMeta(['expected' => 'meta'])->withMeta(['another' => 'one'])
                ->withLinks([Link::self('self.com')])
        );

        $this->assertSame('{"data":[{"type":"expected-type","id":"expected-id","meta":{}}],"meta":{"original":"meta","expected":"meta","another":"one"},"links":{"related":{"href":"related.com","meta":{}},"self":{"href":"self.com","meta":{}}}}', $serialized);
    }
}
