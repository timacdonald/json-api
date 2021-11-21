<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Exceptions;

use RuntimeException;
use function gettype;
use function is_object;

/**
 * @internal
 */
class ResourceIdentificationException extends RuntimeException
{
    public static function attemptingToDetermineIdFor(mixed $model): self
    {
        return new self('Unable to resolve resource object id for '.self::resolveType($model).'.');
    }

    public static function attemptingToDetermineTypeFor(mixed $model): self
    {
        return new self('Unable to resolve resource object type for '.self::resolveType($model).'.');
    }

    private static function resolveType(mixed $model): string
    {
        return is_object($model)
            ? $model::class
            : gettype($model);
    }
}
