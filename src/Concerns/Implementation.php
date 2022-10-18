<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use TiMacDonald\JsonApi\JsonApiServerImplementation;

trait Implementation
{
    /**
     * @internal
     *
     * @var callable|null
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
        static::$serverImplementationResolver = $resolver;
    }

    /**
     * @internal
     *
     * @return void
     */
    public static function resolveServerImplementationNormally()
    {
        static::$serverImplementationResolver = null;
    }

    /**
     * @internal
     *
     * @return callable
     */
    public static function serverImplementationResolver()
    {
        return static::$serverImplementationResolver ?? fn (): JsonApiServerImplementation => new JsonApiServerImplementation('1.0');
    }
}
