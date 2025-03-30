<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use WeakMap;

use function explode;
use function is_array;

/**
 * @internal
 */
final class Includes
{
    private static ?self $instance;

    /**
     * @var WeakMap<Request, array<string, Collection<int, non-empty-string>>>
     */
    private WeakMap $cache;

    private function __construct(WeakMap $cache = new WeakMap)
    {
        $this->cache = $cache;
    }

    /**
     * @return self
     */
    public static function getInstance()
    {
        return self::$instance ??= new self;
    }

    /**
     * @return array<int, string>
     */
    public function forPrefix(Request $request, string $prefix)
    {
        return $this->rememberIncludes($request, $prefix, function () use ($request, $prefix): Collection {
            return $this->all($request)
                ->when($prefix !== '')
                ->filter(fn (string $include): bool => str_starts_with($include, $prefix))
                ->map(fn ($include): string => (string) Str::of($include)->after($prefix)->before('.'))
                ->uniqueStrict()
                ->values();
        })->all();
    }

    /**
     * @return Collection<int, non-empty-string>
     */
    private function all(Request $request)
    {
        return $this->rememberIncludes($request, '__all__', function () use ($request) {
            $includes = $request->query('include') ?? '';

            if (is_array($includes)) {
                throw new HttpException(400, 'The include parameter must be a comma seperated list of relationship paths.');
            }

            return Collection::make(explode(',', $includes))->filter(fn (string $include): bool => $include !== '');
        });
    }

    /**
     * @infection-ignore-all
     *
     * @param  (callable(): Collection<int, non-empty-string>)  $callback
     * @return Collection<int, non-empty-string>
     */
    private function rememberIncludes(Request $request, string $prefix, callable $callback)
    {
        $this->cache[$request] ??= [];

        return $this->cache[$request][$prefix] ??= $callback();
    }
}
