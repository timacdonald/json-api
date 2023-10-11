<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use TiMacDonald\JsonApi\ResourceIdentifier;

class ResourceIdentifierTest extends TestCase
{
    public function testItSerializes(): void
    {
        $identifier = new ResourceIdentifier('expected-type', 'expected-id', ['expected' => 'meta']);

        $serialized = json_encode($identifier);

        $this->assertSame('{"type":"expected-type","id":"expected-id","meta":{"expected":"meta"}}', $serialized);
    }

    public function testEmptyMetaIsExcluded(): void
    {
        $identifier = new ResourceIdentifier('expected-type', 'expected-id', []);

        $serialized = json_encode($identifier);

        $this->assertSame('{"type":"expected-type","id":"expected-id"}', $serialized);
    }

    public function testMissingMetaIsExcluded(): void
    {
        $identifier = new ResourceIdentifier('expected-type', 'expected-id');

        $serialized = json_encode($identifier);

        $this->assertSame('{"type":"expected-type","id":"expected-id"}', $serialized);
    }

    public function testMetaCanBeAppended(): void
    {
        $identifier = (new ResourceIdentifier('expected-type', 'expected-id', ['original' => 'meta']));

        $serialized = json_encode(
            $identifier->withMeta(['expected' => 'meta'])->withMeta(["another" => "one"])
        );

        $this->assertSame('{"type":"expected-type","id":"expected-id","meta":{"original":"meta","expected":"meta","another":"one"}}', $serialized);
    }
}
