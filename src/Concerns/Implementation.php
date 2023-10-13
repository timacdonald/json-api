<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Http\Request;
use TiMacDonald\JsonApi\ServerImplementation;

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
     * @param (callable(): JsonApiServerImplementation) $callback
     * @return void
     */
    public static function resolveServerImplementationUsing(callable $callback)
    {
        self::$serverImplementationResolver = $callback;
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
     * @return (callable(Request): JsonApiServerImplementation)
     */
    public static function serverImplementationResolver()
    {
        return self::$serverImplementationResolver ??= fn (Request $request): ServerImplementation => new ServerImplementation('1.0');
    }
}
