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
     * @param mixed $resource
     * @return static
     */
    public static function from($resource)
    {
        return new static('Unknown relationship encoutered. Relationships should always return a class that extend '.JsonApiResource::class.' or '.JsonApiResourceCollection::class.'. Instead found ['.static::determineType($resource).'].');
    }

    /**
     * @param mixed $resource
     * @return string
     */
    private static function determineType($resource)
    {
        return is_object($resource) ? $resource::class : gettype($resource);
    }
}
