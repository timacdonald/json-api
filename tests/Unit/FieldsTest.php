<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Tests\TestCase;
use TiMacDonald\JsonApi\Support\Fields;

class FieldsTest extends TestCase
{
    public function testItHandlesMultipleRequests(): void
    {
        $requests = [
            Request::create('https://example.com?fields[a]=a'),
            Request::create('https://example.com?fields[b]=b'),
        ];

        $fields = [
            Fields::getInstance()->parse($requests[0], 'a'),
            Fields::getInstance()->parse($requests[1], 'b'),
        ];

        $this->assertSame(['a'], $fields[0]);
        $this->assertSame(['b'], $fields[1]);
    }

    public function testItHandlesEmptyValues(): void
    {
        $request = Request::create('https://example.com?fields[a]=');

        $this->assertSame([], Fields::getInstance()->parse($request, 'a'));
    }

    public function testItCachesMultipleRequests(): void
    {
        $requests = [
            Request::create('https://example.com?fields[a]=a'),
            Request::create('https://example.com?fields[b]=b'),
            Request::create('https://example.com?fields[c]='),
            Request::create('https://example.com?fields[d]='),
        ];
        // ensure it caches via the null check. Laravel handles this casting to
        // null for empty parameters.
        $requests[3]->query->set('fields', ['d' => null]);

        $this->assertSame(Fields::getInstance()->parse($requests[0], 'a'), ['a']);
        $this->assertSame(Fields::getInstance()->parse($requests[0], 'b'), null);
        $this->assertSame(Fields::getInstance()->parse($requests[1], 'b'), ['b']);
        $this->assertSame(Fields::getInstance()->parse($requests[2], 'c'), []);
        $this->assertSame(Fields::getInstance()->parse($requests[3], 'd'), []);
        $this->assertSame(Fields::getInstance()->parse($requests[0], 'a'), ['a']);
        $this->assertSame(Fields::getInstance()->parse($requests[0], 'b'), null);
        $this->assertSame(Fields::getInstance()->parse($requests[1], 'b'), ['b']);
        $this->assertSame(Fields::getInstance()->parse($requests[2], 'c'), []);
        $this->assertSame(Fields::getInstance()->parse($requests[3], 'd'), []);

        $this->assertSame([
            $requests[0],
            $requests[0],
            $requests[1],
            $requests[2],
            $requests[3],
        ], Fields::getInstance()->cache()->pluck('request')->map->get()->all());
    }
}
