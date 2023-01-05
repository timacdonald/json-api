<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use WeakMap;
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
     * @var WeakMap<Request, array<string>>
     */
    private $cache;

    private function __construct()
    {
        $this->cache = new WeakMap;
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
    public function forPrefix($request, $prefix)
    {
        return $this->rememberIncludes($request, $prefix, function () use ($request, $prefix) {
           return $this->all($request)
                ->when($prefix !== '', function (Collection $includes) use ($prefix): Collection {
                    return $includes->filter(fn (string $include): bool => str_starts_with($include, $prefix));
                })
                ->map(fn ($include): string => Str::of($include)->after($prefix)->before('.')->toString())
                ->uniqueStrict()
                ->values();
        })->all();
    }

    /**
     * @param Request $request
     * @param string $prefix
     * @return array<string>
     */
    private function all($request)
    {
        return $this->rememberIncludes($request, '__all__', function () use ($request) {
            $includes = $request->query('include') ?? '';

            abort_if(is_array($includes), 400, 'The include parameter must be a comma seperated list of relationship paths.');

            return Collection::make(explode(',', $includes))->filter(fn (string $include): bool => $include !== '');
        });
    }

    /**
     * @infection-ignore-all
     *
     * @param Request $request
     * @param string $prefix
     * @param callable $callback
     * @return Collection<string>
     */
    private function rememberIncludes($request, $prefix, $callback)
    {
        $this->cache[$request] ??= [];

        return $this->cache[$request][$prefix] ??= $callback();
    }

    /**
     * @return void
     */
    public function flush()
    {
        $this->cache = new WeakMap();
    }
}
