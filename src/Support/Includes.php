<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use Closure;
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
    private static ?Includes $instance;

    /**
     * @var array<string, Collection>
     */
    private array $cache = [];

    private function __construct()
    {
        //
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function parse(Request $request, string $prefix): Collection
    {
        return $this->rememberIncludes($prefix, function () use ($request, $prefix): Collection {
            $includes = $request->query('include') ?? '';

            abort_if(is_array($includes), 400, 'The include parameter must be a comma seperated list of relationship paths.');

            return Collection::make(explode(',', $includes))
                ->when($prefix !== '', function (Collection $includes) use ($prefix): Collection {
                    return $includes->filter(fn (string $include): bool => str_starts_with($include, $prefix));
                })
                ->map(fn ($include): string => Str::before(Str::after($include, $prefix), '.'))
                ->uniqueStrict()
                ->filter(fn (string $include): bool => $include !== '');
        });
    }

    /**
     * @infection-ignore-all
     */
    private function rememberIncludes(string $prefix, Closure $callback): Collection
    {
        return $this->cache[$prefix] ??= $callback();
    }

    public function flush(): void
    {
        $this->cache = [];
    }

    /**
     * @return array<string, Collection>
     */
    public function cache(): array
    {
        return $this->cache;
    }
}
