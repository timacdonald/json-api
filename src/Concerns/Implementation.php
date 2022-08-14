<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use TiMacDonald\JsonApi\JsonApiServerImplementation;

trait Implementation
{
    /**
     * @internal
     *
     * @var ?callable
     */
    private static $serverImplementationResolver = null;

    /**
     * @api
     *
     * @param callable $resolver
     * @return void
     */
    public static function resolveServerImplementationUsing($resolver)
    {
        self::$serverImplementationResolver = $resolver;
    }

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
    public static function serverImplementationResolver(): callable
    {
        return self::$serverImplementationResolver ?? fn (): JsonApiServerImplementation => new JsonApiServerImplementation('1.0');
    }
}
