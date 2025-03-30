<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use TiMacDonald\JsonApi\Support\Fields;

class FieldsTest extends TestCase
{
    public function test_it_is_a_singleton(): void
    {
        $this->assertSame(Fields::getInstance(), Fields::getInstance());
    }

    public function test_it_handles_multiple_requests(): void
    {
        $requests = [
            Request::create('https://example.com?fields[a]=a'),
            Request::create('https://example.com?fields[b]=b'),
        ];

        $fields = [
            Fields::getInstance()->parse($requests[0], 'a', true),
            Fields::getInstance()->parse($requests[1], 'b', true),
        ];

        $this->assertSame(['a'], $fields[0]);
        $this->assertSame(['b'], $fields[1]);
    }

    public function test_it_handles_empty_values(): void
    {
        $request = Request::create('https://example.com?fields[a]=');

        $this->assertSame([], Fields::getInstance()->parse($request, 'a', true));
    }

    public function test_it_aborts_when_fields_is_not_an_array(): void
    {
        Application::getInstance();
        $request = Request::create('https://example.com?fields=as');

        try {
            Fields::getInstance()->parse($request, 'a', true);
            $this->fail('Exception should have been thrown');
        } catch (HttpException $e) {
            $this->assertSame('The fields parameter must be an array of resource types.', $e->getMessage());
            $this->assertSame(400, $e->getStatusCode());
        } catch (Throwable) {
            $this->fail('Http exception should have been thrown');
        }
    }

    public function test_it_must_provide_string_for_fields(): void
    {
        Application::getInstance();
        $request = Request::create('https://example.com?fields[foo][bar]=1');

        try {
            Fields::getInstance()->parse($request, 'foo', true);
            $this->fail('Exception should have been thrown');
        } catch (HttpException $e) {
            $this->assertSame('The fields parameter value must be a comma seperated list of attributes.', $e->getMessage());
            $this->assertSame(400, $e->getStatusCode());
        } catch (Throwable) {
            $this->fail('Http exception should have been thrown');
        }
    }

    public function test_when_requesting_empty_list_it_returns_empty_array()
    {
        $request = Request::create('https://example.com?fields[foo]=');

        $includes = Fields::getInstance()->parse($request, 'foo', true);

        $this->assertSame([], $includes);
    }

    public function test_when_not_specifying_resource_fields_returns_null()
    {
        $request = Request::create('https://example.com?fields[foo]=bar');

        $includes = Fields::getInstance()->parse($request, 'baz', false);

        $this->assertNull($includes);
    }

    public function test_when_not_specifying_resource_fields_returns_empty_array_for_minimal_fields()
    {
        $request = Request::create('https://example.com?fields[foo]=bar');

        $includes = Fields::getInstance()->parse($request, 'baz', true);

        $this->assertSame([], $includes);
    }
}
