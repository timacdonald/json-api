<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use WeakReference;
use function explode;
use function array_key_exists;
use function is_string;

/**
 * @internal
 */
class Fields
{
    private static ?Fields $instance;

    private Collection $cache;

    private function __construct()
    {
        $this->cache = new Collection([]);
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function parse(Request $request, string $resourceType): ?array
    {
        $result = $this->cache->first(fn (array $item): bool => $item['resourceType'] === $resourceType && $item['request']->get() === $request);

        if ($result !== null) {
            return $result['fields'];
        }

        $typeFields = $request->query('fields') ?? [];

        if (is_string($typeFields)) {
            throw new HttpException(400, 'The fields parameter must be an array of resource types.');
        }

        if (! array_key_exists($resourceType, $typeFields)) {
            $this->cache[] = [
                'fields' => null,
                'request' => WeakReference::create($request),
                'resourceType' => $resourceType,
            ];

            return null;
        }

        $fields = $typeFields[$resourceType];

        if ($fields === null) {
            $this->cache[] = [
                'fields' => [],
                'request' => WeakReference::create($request),
                'resourceType' => $resourceType,
            ];

            return [];
        }

        if (! is_string($fields)) {
            throw new HttpException(400, 'The fields parameter value must be a comma seperated list of attributes.');
        }

        $fields = explode(',', $fields);

        $this->cache[] = [
            'fields' => $fields,
            'request' => WeakReference::create($request),
            'resourceType' => $resourceType,
        ];


        return $fields;
    }

    public function flush(): void
    {
        $this->cache = new Collection([]);
    }
}
