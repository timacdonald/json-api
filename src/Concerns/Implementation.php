<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Closure;
use TiMacDonald\JsonApi\JsonApiServerImplementation as ServerImplementation;

/**
 * @internal
 */
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
        return self::$serverImplementationResolver ?? fn () => new ServerImplementation('1.0');
    }
}
