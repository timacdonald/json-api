<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use WeakReference;

use function explode;
use function is_array;

/**
 * @internal
 */
class Includes
{
    private static ?Includes $instance;

    private Collection $cache;

    private function __construct()
    {
        $this->cache = new Collection([]);
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function parse(Request $request, string $prefix): Collection
    {
        $result = $this->cache->first(fn (array $item): bool => $item['prefix'] === $prefix && $item['request']->get() === $request);

        if ($result !== null) {
            return $result['includes'];
        }

        $includes = $request->query('include') ?? '';

        if (is_array($includes)) {
            abort(400, 'The include parameter must be a comma seperated list of relationship paths.');
        }

        $includes = Collection::make(explode(',', $includes))
            ->when($prefix !== '', function (Collection $includes) use ($prefix): Collection {
                return $includes->filter(fn (string $include): bool => Str::startsWith($include, $prefix));
            })
            ->map(fn (string $include): string => Str::before(Str::after($include, $prefix), '.'))
            ->uniqueStrict()
            ->filter(fn (string $include): bool => $include !== '');

        $this->cache[] = [
            'includes' => $includes,
            'request' => WeakReference::create($request),
            'prefix' => $prefix,
        ];

        return $includes;
    }

    public function flush(): void
    {
        $this->cache = new Collection([]);
    }

    public function cache(): Collection
    {
        return $this->cache;
    }
}
