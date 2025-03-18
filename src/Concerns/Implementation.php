<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use TiMacDonald\JsonApi\ServerImplementation;

trait Implementation
{
    /**
     * @api
     *
     * @param  (callable(): ServerImplementation)  $callback
     * @return void
     */
    public static function resolveServerImplementationUsing(callable $callback)
    {
        App::instance(self::class.':$serverImplementationResolver', $callback);
    }

    /**
     * @internal
     *
     * @return (callable(Request): (ServerImplementation|null))
     */
    public static function serverImplementationResolver()
    {
        return App::bound(self::class.':$serverImplementationResolver')
            ? App::make(self::class.':$serverImplementationResolver')
            : fn () => null;
    }
}
