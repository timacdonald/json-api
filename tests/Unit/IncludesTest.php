<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Tests\TestCase;
use TiMacDonald\JsonApi\Support\Includes;

class IncludesTest extends TestCase
{
    public function testItIsASingleton(): void
    {
        $this->assertSame(Includes::getInstance(), Includes::getInstance());
    }

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

    public function testItHandlesMultipleRequestsWithCacheClearing(): void
    {
        $requests = [
            Request::create('https://example.com?include=a'),
            Request::create('https://example.com?include=b'),
        ];
        $includes = [];

        $includes[] = Includes::getInstance()->parse($requests[0], '');
        Includes::getInstance()->flush();
        $includes[] = Includes::getInstance()->parse($requests[1], '');

        $this->assertSame($includes[0]->all(), ['a']);
        $this->assertSame($includes[1]->all(), ['b']);
    }
}
