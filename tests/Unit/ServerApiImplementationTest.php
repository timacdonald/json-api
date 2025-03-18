<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use TiMacDonald\JsonApi\ServerImplementation;

class ServerApiImplementationTest extends TestCase
{
    public function test_it_serializes(): void
    {
        $instance = (new ServerImplementation('5.0', [
            'expected' => 'meta',
        ]))->withMeta([
            'more' => 'meta',
        ]);

        $json = json_encode($instance);

        self::assertSame('{"version":"5.0","meta":{"expected":"meta","more":"meta"}}', $json);
    }

    public function test_empty_meta_is_excluded(): void
    {
        $instance = new ServerImplementation('5.0', []);

        $json = json_encode($instance);

        $this->assertSame('{"version":"5.0"}', $json);
    }

    public function test_missing_meta_is_excluded(): void
    {
        $instance = new ServerImplementation('5.0');

        $json = json_encode($instance);

        $this->assertSame('{"version":"5.0"}', $json);
    }
}
