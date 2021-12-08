<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Tests\TestCase;
use TiMacDonald\JsonApi\Support\Fields;

class FieldsTest extends TestCase
{
    public function testItIsASingleton(): void
    {
        $this->assertSame(Fields::getInstance(), Fields::getInstance());
    }

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
}
