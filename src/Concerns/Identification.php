<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

/**
 * @internal
 */
trait Identification
{
    private static ?Closure $idResolver;

    private static ?Closure $typeResolver;

    public static function resolveIdNormally(): void
    {
        self::$idResolver = null;
    }

    public static function resolveTypeNormally(): void
    {
        self::$typeResolver = null;
    }
}
