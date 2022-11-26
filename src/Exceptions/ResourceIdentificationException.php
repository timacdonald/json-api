<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Exceptions;

use RuntimeException;

use function gettype;
use function is_object;

/**
 * @internal
 */
final class ResourceIdentificationException extends RuntimeException
{
    /**
     * @param mixed $model
     * @return static
     */
    public static function attemptingToDetermineIdFor($model)
    {
        return new static('Unable to resolve resource object id for ['.static::determineType($model).'].');
    }

    /**
     * @param mixed $model
     * @return static
     */
    public static function attemptingToDetermineTypeFor($model)
    {
        return new static('Unable to resolve resource object type for ['.static::determineType($model).'].');
    }

    /**
     * @param mixed $model
     * @return string
     */
    private static function determineType($model)
    {
        return is_object($model) ? $model::class : gettype($model);
    }
}
