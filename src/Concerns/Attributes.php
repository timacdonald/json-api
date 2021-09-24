<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TiMacDonald\JsonApi\Support\Fields;

trait Attributes
{
    private static bool $minimalAttributes = false;

    private static bool $includeAvailableAttributesViaMeta = false;

    /**
     * @return array<string, mixed>
     */
    private function resolveAttributes(Request $request): array
    {
        return once(fn () => $this->toAttributes($request));
    }

    /**
     * @return array<string, mixed>
     */
    private function parseAttributes(Request $request): array
    {
        return Collection::make($this->resolveAttributes($request))
            ->only($this->fields($request))
            ->map(fn (mixed $value): mixed => value($value, $request))
            ->all();
    }

    /**
     * @return array<string>
     */
    private function availableAttributes(Request $request): array
    {
        return array_keys($this->resolveAttributes($request));
    }

    /**
     * @return null|array<string>
     */
    private function fields(Request $request): ?array
    {
        $fields = Fields::parse($request, static::resourceType($this->resource));

        if ($fields !== null) {
            return $fields;
        }

        return static::$minimalAttributes
            ? []
            : null;
    }
}
