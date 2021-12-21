<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use TiMacDonald\JsonApi\Contracts\Flushable;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;

/**
 * @internal
 */
final class Cache
{
    /**
     * @param Flushable $resource
     */
    public static function flush($resource): void
    {
        $resource->flush();

        Includes::getInstance()->flush();

        Fields::getInstance()->flush();
    }
}
