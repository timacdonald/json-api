<?php

namespace TiMacDonald\JsonApi\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Fields
{
    /**
     * @return ?array<string>
     */
    public static function parse(Request $request, string $resourceType): ?array
    {
        $typeFields = $request->query('fields') ?? [];

        if (is_string($typeFields)) {
            throw new HttpException(400, 'The fields parameter must be an array of resource types.');
        }

        if (! array_key_exists($resourceType, $typeFields)) {
            return null;
        }

        $fields = $typeFields[$resourceType];

        if ($fields === null) {
            return [];
        }

        if (! is_string($fields)) {
            throw new HttpException(400, 'The fields parameter value must be a comma seperated list of attributes.');
        }

        return explode(',', $fields);
    }
}
