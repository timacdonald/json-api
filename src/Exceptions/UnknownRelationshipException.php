<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Exceptions;

use Exception;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;

use function gettype;
use function is_object;

final class UnknownRelationshipException extends Exception
{
    /**
     * @internal
     *
     * @return self
     */
    public static function from(mixed $resource)
    {
        return new self('Unknown relationship encountered. Relationships should always return a class that extends '.JsonApiResource::class.' or '.JsonApiResourceCollection::class.'. Instead found ['.static::determineType($resource).'].');
    }

    /**
     * @internal
     *
     * @return string
     */
    private static function determineType(mixed $resource)
    {
        return is_object($resource)
            ? $resource::class
            : gettype($resource);
    }
}
