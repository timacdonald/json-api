<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use Closure;
use Illuminate\Http\Request;
use function array_key_exists;
use function explode;
use function is_string;

/**
 * @internal
 */
final class Fields
{
    private static ?Fields $instance;

    private array $cache = [];

    private function __construct()
    {
        //
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function parse(Request $request, string $resourceType, bool $minimalAttributes): ?array
    {
        return $this->rememberResourceType("type:{$resourceType};minimal:{$minimalAttributes};", function () use ($request, $resourceType, $minimalAttributes): ?array {
            $typeFields = $request->query('fields') ?? [];

            abort_if(is_string($typeFields), 400, 'The fields parameter must be an array of resource types.');

            if (! array_key_exists($resourceType, $typeFields)) {
                return $minimalAttributes
                    ? []
                    : null;
            }

            $fields = $typeFields[$resourceType];

            if ($fields === null) {
                return [];
            }

            abort_if(! is_string($fields), 400, 'The fields parameter value must be a comma seperated list of attributes.');

            return array_filter(explode(',', $fields), fn (string $value): bool => $value !== '');
        });
    }

    /**
     * @infection-ignore-all
     */
    private function rememberResourceType(string $resourceType, Closure $callback): ?array
    {
        return $this->cache[$resourceType] ??= $callback();
    }

    public function flush(): void
    {
        $this->cache = [];
    }

    public function cache(): array
    {
        return $this->cache;
    }
}
