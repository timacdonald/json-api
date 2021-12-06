<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Exceptions;

use RuntimeException;
use function get_class;
use function gettype;
use function is_object;

/**
 * @internal
 */
class ResourceIdentificationException extends RuntimeException
{
    /**
     * @param mixed $model
     */
    public static function attemptingToDetermineIdFor($model): self
    {
        return new self('Unable to resolve resource object id for '.self::resolveType($model).'.');
    }

    /**
     * @param mixed $model
     */
    public static function attemptingToDetermineTypeFor($model): self
    {
        return new self('Unable to resolve resource object type for '.self::resolveType($model).'.');
    }

    /**
     * @param mixed $model
     */
    private static function resolveType($model): string
    {
        return is_object($model)
            ? get_class($model)
            : gettype($model);
    }
}
