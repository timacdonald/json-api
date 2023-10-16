<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Tests\Resources\BasicJsonApiResource;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\Link;

class LinkTest extends TestCase
{
    public function testItSerializes(): void
    {
        $link = Link::related('https://related.com', ['expected' => 'meta']);

        $serialized = json_encode($link);

        $this->assertSame('{"href":"https:\/\/related.com","meta":{"expected":"meta"}}', $serialized);
    }

    public function testEmptyMetaIsExluded(): void
    {
        $link = Link::related('https://related.com', []);

        $serialized = json_encode($link);

        $this->assertSame('{"href":"https:\/\/related.com"}', $serialized);
    }

    public function testMissingMetaIsExcluded(): void
    {
        $link = Link::related('https://related.com');

        $serialized = json_encode($link);

        $this->assertSame('{"href":"https:\/\/related.com"}', $serialized);
    }

    public function testMetaCanBeAppended(): void
    {
        $link = Link::related('https://related.com', ['original' => 'meta']);

        $serialized = json_encode(
            $link->withMeta(['expected' => 'meta'])->withMeta(['another' => 'one'])
        );

        $this->assertSame('{"href":"https:\/\/related.com","meta":{"original":"meta","expected":"meta","another":"one"}}', $serialized);
    }

    public function testTypeIsSet(): void
    {
        $this->assertSame('related', Link::related('https://related.com')->key);
        $this->assertSame('self', Link::self('https://self.com')->key);
        $this->assertSame('expected-type', (new Link('expected-type', 'https://another.com'))->key);
    }

    public function testItCanUseHash()
    {
        JsonApiResource::resolveIdUsing(fn () => 'id');
        JsonApiResource::resolveTypeUsing(fn () => 'type');
        $resource = new BasicJsonApiResource(null);
        $request = Request::create('http://bar.com');

        $resource->withLinks([
            'foo' => 'http://foo.com',
        ]);

        $links = json_encode($resource->toArray($request)['links']);

        $this->assertSame('{"foo":{"href":"http:\/\/foo.com"}}', $links);
    }
}
