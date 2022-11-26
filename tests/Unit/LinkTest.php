<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use TiMacDonald\JsonApi\Link;

class LinkTest extends TestCase
{
    public function testItSerializes(): void
    {
        $link = Link::related('https://related.com', ['expected' => 'meta']);

        $serialized = json_encode($link);

        $this->assertSame('{"href":"https:\/\/related.com","meta":{"expected":"meta"}}', $serialized);
    }

    public function testEmptyMetaIsObject(): void
    {
        $link = Link::related('https://related.com', []);

        $serialized = json_encode($link);

        $this->assertSame('{"href":"https:\/\/related.com","meta":{}}', $serialized);
    }

    public function testMissingMetaIsObject(): void
    {
        $link = Link::related('https://related.com');

        $serialized = json_encode($link);

        $this->assertSame('{"href":"https:\/\/related.com","meta":{}}', $serialized);
    }

    public function testMetaCanBeAppended(): void
    {
        $link = Link::related('https://related.com', ['original' => 'meta']);

        $serialized = json_encode(
            $link->withMeta(['expected' => 'meta'])->withMeta(["another" => "one"])
        );

        $this->assertSame('{"href":"https:\/\/related.com","meta":{"original":"meta","expected":"meta","another":"one"}}', $serialized);
    }

    public function testTypeIsSet(): void
    {
        $this->assertSame('related', Link::related('https://related.com')->type);
        $this->assertSame('self', Link::self('https://self.com')->type);
        $this->assertSame('expected-type', (new Link('expected-type', 'https://another.com'))->type);
    }
}
