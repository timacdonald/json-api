<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use function explode;
use function is_array;

/**
 * @internal
 */
final class Includes
{
    /**
     * @var static|null
     */
    private static $instance = null;

    /**
     * @var array<string, array<string>>
     */
    private $cache = [];

    private function __construct()
    {
        //
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        return static::$instance ??= new static();
    }

    /**
     * @param Request $request
     * @param string $prefix
     * @return array<string>
     */
    public function parse($request, $prefix)
    {
        return $this->rememberIncludes($prefix, function () use ($request, $prefix): array {
            $includes = $request->query('include') ?? '';

            abort_if(is_array($includes), 400, 'The include parameter must be a comma seperated list of relationship paths.');

            return Collection::make(explode(',', $includes))
                ->when($prefix !== '', function (Collection $includes) use ($prefix): Collection {
                    return $includes->filter(fn (string $include): bool => str_starts_with($include, $prefix));
                })
                ->map(fn ($include): string => Str::before(Str::after($include, $prefix), '.'))
                ->uniqueStrict()
                ->filter(fn (string $include): bool => $include !== '')
                ->all();
        });
    }

    /**
     * @infection-ignore-all
     *
     * @param string $prefix
     * @param callable $callback
     * @return array<string>
     */
    private function rememberIncludes($prefix, $callback)
    {
        return $this->cache[$prefix] ??= $callback();
    }

    /**
     * @return void
     */
    public function flush()
    {
        $this->cache = [];
    }

    /**
     * @return array<string, array<string>>
     */
    public function cache()
    {
        return $this->cache;
    }
}
