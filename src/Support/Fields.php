<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function explode;
use function array_key_exists;
use function is_string;

class Fields
{
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
