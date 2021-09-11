<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use TiMacDonald\JsonApi\Contracts\ResourceIdResolver;
use TiMacDonald\JsonApi\Contracts\ResourceTypeResolver;

trait ResourceIdentification
{
    private static function resourceId(mixed $resource): string
    {
        return app(ResourceIdResolver::class)($resource);
    }

    private static function resourceType(mixed $resource): string
    {
        return app(ResourceTypeResolver::class)($resource);
    }
}
