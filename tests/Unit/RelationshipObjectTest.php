<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use TiMacDonald\JsonApi\Link;
use TiMacDonald\JsonApi\RelationshipObject;
use TiMacDonald\JsonApi\ResourceIdentifier;

class RelationshipObjectTest extends TestCase
{
    public function testItSerializes(): void
    {
        $link = RelationshipObject::toOne(new ResourceIdentifier('expected-type', 'expected-id'), [new Link('expected', 'link')], ['expected' => 'meta']);

        $serialized = json_encode($link);

        $this->assertSame('{"data":{"type":"expected-type","id":"expected-id"},"meta":{"expected":"meta"},"links":{"expected":{"href":"link"}}}', $serialized);
    }

    public function testEmptyMetaAndLinksIsExcluded(): void
    {
        $link = RelationshipObject::toOne(new ResourceIdentifier('expected-type', 'expected-id'), [], []);

        $serialized = json_encode($link);

        $this->assertSame('{"data":{"type":"expected-type","id":"expected-id"}}', $serialized);
    }

    public function testMissingMetaAndLinksIsExcluded(): void
    {
        $link = RelationshipObject::toOne(new ResourceIdentifier('expected-type', 'expected-id'));

        $serialized = json_encode($link);

        $this->assertSame('{"data":{"type":"expected-type","id":"expected-id"}}', $serialized);
    }

    public function testMetaAndLinksCanBeAppended(): void
    {
        $link = RelationshipObject::toMany([new ResourceIdentifier('expected-type', 'expected-id')], [Link::related('related.com')], ["original" => "meta"]);

        $serialized = json_encode(
            $link->withMeta(['expected' => 'meta'])->withMeta(['another' => 'one'])
                ->withLinks([Link::self('self.com')])
        );

        $this->assertSame('{"data":[{"type":"expected-type","id":"expected-id"}],"meta":{"original":"meta","expected":"meta","another":"one"},"links":{"related":{"href":"related.com"},"self":{"href":"self.com"}}}', $serialized);
    }
}
