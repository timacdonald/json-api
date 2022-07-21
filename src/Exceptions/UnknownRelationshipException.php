<?php

namespace TiMacDonald\JsonApi\Exceptions;

use Exception;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;

final class UnknownRelationshipException extends Exception
{
    public static function from(mixed $resource)
    {
        return new self('Unknown relationship encoutered. Relationships should always return a class that extend '.JsonApiResource::class.' or '.JsonApiResourceCollection::class.'. Instead found ['self::determineType($resouce).'].');
    }

    private static function determineType(mixed $resource): string
    {
        return is_object($resource)
            ? get_class($resource)
            : gettype($resource);
    }
}
