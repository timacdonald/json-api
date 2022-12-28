<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use TiMacDonald\JsonApi\Support\Includes;

class IncludesTest extends TestCase
{
    protected function tearDown(): void
    {
        Includes::getInstance()->flush();

        parent::tearDown();
    }

    public function testItIsASingleton(): void
    {
        $this->assertSame(Includes::getInstance(), Includes::getInstance());
    }

    public function testItRemovesEmptyStringIncludes(): void
    {
        $request = Request::create('https://example.com?include=a');

        $includes = Includes::getInstance()->forPrefix($request, 'a.');

        $this->assertCount(0, $includes);
    }

    public function testItRemovesDuplicates(): void
    {
        $request = Request::create('https://example.com?include=a.b,a.b.c');

        $includes = Includes::getInstance()->forPrefix($request, 'a.');

        $this->assertCount(1, $includes);
    }

    public function testItHandlesMultipleRequestsWithCacheClearing(): void
    {
        $requests = [
            Request::create('https://example.com?include=a'),
            Request::create('https://example.com?include=b'),
        ];
        $includes = [];

        $includes[] = Includes::getInstance()->forPrefix($requests[0], '');
        Includes::getInstance()->flush();
        $includes[] = Includes::getInstance()->forPrefix($requests[1], '');

        $this->assertSame($includes[0], ['a']);
        $this->assertSame($includes[1], ['b']);
    }

    public function testItAbortsWhenIncludesIsNotAString(): void
    {
        Application::getInstance();
        $request = Request::create('https://example.com?include[]=');

        try {
            Includes::getInstance()->forPrefix($request, '');
            $this->fail('Exception should have been thrown');
        } catch (HttpException $e) {
            $this->assertSame('The include parameter must be a comma seperated list of relationship paths.', $e->getMessage());
            $this->assertSame(400, $e->getStatusCode());
        } catch (Throwable) {
            $this->fail('Http exception should have been thrown');
        }
    }

    public function testItCanClearTheCache(): void
    {
        $requests = [
            Request::create('https://example.com?include=foo.a,bar.a'),
            Request::create('https://example.com?include=foo.b'),
        ];

        Includes::getInstance()->forPrefix($requests[0], 'foo.');
        Includes::getInstance()->forPrefix($requests[0], 'bar.');
        $this->assertSame([
            'foo.' => [
                'a'
            ],
            'bar.' => [
                'a'
            ],
        ], Includes::getInstance()->cache());
    }
}
