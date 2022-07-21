<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Closure;
use TiMacDonald\JsonApi\JsonApiServerImplementation;

// TODO: Should this be something you control on a per response level instead? withImplementation or something?
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
        return self::$serverImplementationResolver ?? fn (): JsonApiServerImplementation => new JsonApiServerImplementation('1.0');
    }
}
