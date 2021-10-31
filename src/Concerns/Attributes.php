<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\Support\Fields;
use function value;

/**
 * @internal
 */
trait Attributes
{
    /**
     * @internal
     */
    private static bool $minimalAttributes = false;

    /**
     * @internal
     */
    public static function maximalAttributes(): void
    {
        static::$minimalAttributes = false;
    }

    /**
     * @internal
     */
    private function requestedAttributes(Request $request): Collection
    {
        return Collection::make($this->resolveAttributes($request))
            ->only($this->fields($request))
            ->map(fn (mixed $value): mixed => value($value, $request));
    }

    /**
     * @internal
     */
    private function fields(Request $request): ?array
    {
        $fields = Fields::parse($request, $this->toType($request));

        if ($fields !== null) {
            return $fields;
        }

        return static::$minimalAttributes
            ? []
            : null;
    }

    /**
     * @internal
     */
    private function resolveAttributes(Request $request): array
    {
        return once(fn () => $this->toAttributes($request));
    }
}
