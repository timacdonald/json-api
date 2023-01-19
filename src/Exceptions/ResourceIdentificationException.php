<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Exceptions;

use RuntimeException;

use function gettype;
use function is_object;

final class ResourceIdentificationException extends RuntimeException
{
    /**
     * @internal
     *
     * @return self
     */
    public static function attemptingToDetermineIdFor(mixed $resource)
    {
        return new static('Unable to resolve resource object id for ['.static::determineType($resource).'].');
    }

    /**
     * @internal
     *
     * @return self
     */
    public static function attemptingToDetermineTypeFor(mixed $resource)
    {
        return new static('Unable to resolve resource object type for ['.static::determineType($resource).'].');
    }

    /**
     * @internal
     *
     * @return string
     */
    private static function determineType(mixed $resource)
    {
        return is_object($resource) ? $resource::class : gettype($resource);
    }
}
