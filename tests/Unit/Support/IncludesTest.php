<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use TiMacDonald\JsonApi\Support\Includes;

class IncludesTest extends TestCase
{
    public function test_it_is_a_singleton(): void
    {
        $this->assertSame(Includes::getInstance(), Includes::getInstance());
    }

    public function test_it_removes_empty_string_includes(): void
    {
        $request = Request::create('https://example.com?include=a');

        $includes = Includes::getInstance()->forPrefix($request, 'a.');

        $this->assertCount(0, $includes);
    }

    public function test_it_removes_duplicates(): void
    {
        $request = Request::create('https://example.com?include=a.b,a.b.c');

        $includes = Includes::getInstance()->forPrefix($request, 'a.');

        $this->assertCount(1, $includes);
    }

    public function test_it_handles_multiple_requests(): void
    {
        $requests = [
            Request::create('https://example.com?include=a'),
            Request::create('https://example.com?include=b'),
        ];
        $includes = [];

        $includes[] = Includes::getInstance()->forPrefix($requests[0], '');
        $includes[] = Includes::getInstance()->forPrefix($requests[1], '');

        $this->assertSame($includes[0], ['a']);
        $this->assertSame($includes[1], ['b']);
    }

    public function test_it_aborts_when_includes_is_not_a_string(): void
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
}
