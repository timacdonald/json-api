<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\Support\Fields;
use function array_keys;
use function value;

/**
 * @internal
 */
trait Attributes
{
    private static bool $minimalAttributes = false;

    private static bool $includeAvailableAttributesViaMeta = false;

    public static function maximalAttributes(): void
    {
        static::$minimalAttributes = false;
    }

    public static function excludeAvailableAttributesViaMeta(): void
    {
        static::$includeAvailableAttributesViaMeta = false;
    }

    private function requestedAttributes(Request $request): Collection
    {
        return Collection::make($this->toAttributes($request))
            ->only($this->fields($request))
            ->map(fn (mixed $value): mixed => value($value, $request));
    }

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

    private function availableAttributes(Request $request): array
    {
        return array_keys($this->toAttributes($request));
    }
}
