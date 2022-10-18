<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Exceptions;

use Exception;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;

use function get_class;
use function gettype;
use function is_object;

/**
 * @internal
 */
final class UnknownRelationshipException extends Exception
{
    /**
     * @param mixed $model
     * @return static
     */
    public static function from($resource)
    {
        return new static('Unknown relationship encoutered. Relationships should always return a class that extend '.JsonApiResource::class.' or '.JsonApiResourceCollection::class.'. Instead found ['.static::determineType($resouce).'].');
    }

    /**
     * @param mixed $model
     * @return string
     */
    private static function determineType($resource)
    {
        return is_object($resource) ? get_class($resource) : gettype($resource);
    }
}
