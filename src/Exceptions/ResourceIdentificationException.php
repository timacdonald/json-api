<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Exceptions;

use RuntimeException;

use function get_class;
use function gettype;
use function is_object;

final class ResourceIdentificationException extends RuntimeException
{
    public static function attemptingToDetermineIdFor(mixed $model): self
    {
        return new self('Unable to resolve resource object id for '.self::determineType($model).'.');
    }

    public static function attemptingToDetermineTypeFor(mixed $model): self
    {
        return new self('Unable to resolve resource object type for '.self::determineType($model).'.');
    }

    private static function determineType(mixed $model): string
    {
        return is_object($model)
            ? get_class($model)
            : gettype($model);
    }
}
