<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Closure;
use TiMacDonald\JsonApi\JsonApiServerImplementation;

trait Implementation
{
    /**
     * @internal
     */
    private static ?Closure $serverImplementationResolver = null;

    /**
     * @internal
     */
    public static function resolveServerImplementationNormally(): void
    {
        self::$serverImplementationResolver = null;
    }

    /**
     * @internal
     */
    public static function serverImplementationResolver(): Closure
    {
        return self::$serverImplementationResolver ?? static fn (): JsonApiServerImplementation => new JsonApiServerImplementation('1.0');
    }
}
