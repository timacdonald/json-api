<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use TiMacDonald\JsonApi\JsonApiServerImplementation;

trait Implementation
{
    /**
     * @internal
     *
     * @var (callable(): JsonApiServerImplementation)|null
     */
    private static $serverImplementationResolver;

    /**
     * @api
     *
     * @param (callable(): JsonApiServerImplementation) $resolver
     * @return void
     */
    public static function resolveServerImplementationUsing(callable $resolver)
    {
        self::$serverImplementationResolver = $resolver;
    }

    /**
     * @internal
     *
     * @return void
     */
    public static function resolveServerImplementationNormally()
    {
        self::$serverImplementationResolver = null;
    }

    /**
     * @internal
     *
     * @return (callable(): JsonApiServerImplementation)
     */
    public static function serverImplementationResolver()
    {
        return self::$serverImplementationResolver ?? fn (): JsonApiServerImplementation => new JsonApiServerImplementation('1.0');
    }
}
