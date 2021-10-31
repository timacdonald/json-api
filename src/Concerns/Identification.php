<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Closure;

/**
 * @internal
 */
trait Identification
{
    /**
     * @internal
     */
    private static ?Closure $idResolver;

    /**
     * @internal
     */
    private static ?Closure $typeResolver;

    /**
     * @internal
     */
    public static function resolveIdNormally(): void
    {
        self::$idResolver = null;
    }

    /**
     * @internal
     */
    public static function resolveTypeNormally(): void
    {
        self::$typeResolver = null;
    }
}
