<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use TiMacDonald\JsonApi\JsonApiServerImplementation;

class ServerApiImplementationTest extends TestCase
{
    public function testItSerializes(): void
    {
        $instance = (new JsonApiServerImplementation('5.0', [
            'expected' => 'meta',
        ]))->withMeta([
            'more' => 'meta',
        ]);

        $json = json_encode($instance);

        self::assertSame('{"version":"5.0","meta":{"expected":"meta","more":"meta"}}', $json);
    }

    public function testEmptyMetaIsExcluded(): void
    {
        $instance = new JsonApiServerImplementation('5.0', []);

        $json = json_encode($instance);

        $this->assertSame('{"version":"5.0"}', $json);
    }

    public function testMissingMetaIsExcluded(): void
    {
        $instance = new JsonApiServerImplementation('5.0');

        $json = json_encode($instance);

        $this->assertSame('{"version":"5.0"}', $json);
    }
}
