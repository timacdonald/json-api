<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use Illuminate\Http\Request;

use WeakMap;
use function array_key_exists;
use function explode;
use function is_string;

/**
 * @internal
 */
final class Fields
{
    /**
     * @var static|null
     */
    private static $instance = null;

    /**
     * @var WeakMap
     */
    private $cache;

    private function __construct()
    {
        $this->cache = new WeakMap;
    }

    /**
     * @return Fields
     */
    public static function getInstance()
    {
        return static::$instance ??= new static();
    }

    /**
     * @param Request $request
     * @param string $resourceType
     * @param bool $minimalAttributes
     * @return array<string>|null
     */
    public function parse($request, $resourceType, $minimalAttributes)
    {
        return $this->rememberResourceType($request, "type:{$resourceType};minimal:{$minimalAttributes};", function () use ($request, $resourceType, $minimalAttributes): ?array {
            $typeFields = $request->query('fields') ?? [];

            abort_if(is_string($typeFields), 400, 'The fields parameter must be an array of resource types.');

            if (! array_key_exists($resourceType, $typeFields)) {
                return $minimalAttributes
                    ? []
                    : null;
            }

            $fields = $typeFields[$resourceType] ?? '';

            abort_if(! is_string($fields), 400, 'The fields parameter value must be a comma seperated list of attributes.');

            return array_filter(explode(',', $fields), fn (string $value): bool => $value !== '');
        });
    }

    /**
     * @infection-ignore-all
     *
     * @param Request $request
     * @param string $resourceType
     * @param callable $callback
     * @return array<string>|null
     */
    private function rememberResourceType($request, $resourceType, $callback)
    {
        $this->cache[$request] ??= [];

        return $this->cache[$request][$resourceType] ??= $callback();
    }

    /**
     * @return void
     */
    public function flush()
    {
        $this->cache = new WeakMap;
    }
}
