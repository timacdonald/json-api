<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TiMacDonald\JsonApi\Support\Fields;

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

    /**
     * @return Collection<string, mixed>
     */
    private function requestedAttributes(Request $request): Collection
    {
        return Collection::make($this->toAttributes($request))
            ->only($this->fields($request))
            ->map(fn (mixed $value): mixed => value($value, $request));
    }

    /**
     * @return null|array<string>
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
     * @return array<string>
     */
    private function availableAttributes(Request $request): array
    {
        return array_keys($this->toAttributes($request));
    }
}
