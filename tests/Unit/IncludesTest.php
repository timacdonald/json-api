<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Tests\TestCase;
use TiMacDonald\JsonApi\Support\Includes;

class IncludesTest extends TestCase
{
    public function testItRemovesEmptyStringIncludes(): void
    {
        $request = Request::create('https://example.com?include=a');

        $includes = Includes::getInstance()->parse($request, 'a.');

        $this->assertTrue($includes->isEmpty());
    }

    public function testItRemovesDuplicates(): void
    {
        $request = Request::create('https://example.com?include=a.b,a.b.c');

        $includes = Includes::getInstance()->parse($request, 'a.');

        $this->assertCount(1, $includes);
    }

    public function testItHandlesMultipleRequests(): void
    {
        $requests = [
            Request::create('https://example.com?include=a'),
            Request::create('https://example.com?include=b'),
        ];

        $includes = [
            Includes::getInstance()->parse($requests[0], ''),
            Includes::getInstance()->parse($requests[1], ''),
        ];

        $this->assertSame($includes[0]->all(), ['a']);
        $this->assertSame($includes[1]->all(), ['b']);
    }

    public function testItCachesMultipleRequests(): void
    {
        $requests = [
            Request::create('https://example.com?include=a'),
            Request::create('https://example.com?include=b'),
        ];

        Includes::getInstance()->parse($requests[0], '');
        Includes::getInstance()->parse($requests[1], '');
        Includes::getInstance()->parse($requests[0], '');
        Includes::getInstance()->parse($requests[1], '');

        $this->assertSame(Includes::getInstance()->cache()->pluck('request')->map->get()->all(), $requests);
    }
}
