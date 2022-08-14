<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use TiMacDonald\JsonApi\Link;

class LinkTest extends TestCase
{
    public function testItSerializes()
    {
        $link = Link::related('https://related.com', ['expected' => 'meta']);

        $serialized = json_encode($link);

        $this->assertSame('{"href":"https:\/\/related.com","meta":{"expected":"meta"}}', $serialized);
    }

    public function testEmptyMetaIsObject()
    {
        $link = Link::related('https://related.com', []);

        $serialized = json_encode($link);

        $this->assertSame('{"href":"https:\/\/related.com","meta":{}}', $serialized);
    }

    public function testMissingMetaIsObject()
    {
        $link = Link::related('https://related.com');

        $serialized = json_encode($link);

        $this->assertSame('{"href":"https:\/\/related.com","meta":{}}', $serialized);
    }

    public function testMetaCanBeAppended()
    {
        $link = Link::related('https://related.com', ['original' => 'meta']);

        $serialized = json_encode(
            $link->withMeta(['expected' => 'meta'])->withMeta(["another" => "one"])
        );

        $this->assertSame('{"href":"https:\/\/related.com","meta":{"original":"meta","expected":"meta","another":"one"}}', $serialized);
    }

    public function testTypeIsSet()
    {
        $this->assertSame('related', Link::related('https://related.com')->type);
        $this->assertSame('self', Link::self('https://self.com')->type);
        $this->assertSame('expected-type', (new Link('expected-type', 'https://another.com'))->type);
    }
}
