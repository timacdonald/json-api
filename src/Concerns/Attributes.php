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

    /**
     * @return array<string, mixed>
     */
    private function parseAttributes(Request $request): array
    {
        return Collection::make($this->toAttributes($request))
            ->only($this->fields($request))
            ->map(fn (mixed $value): mixed => value($value, $request))
            ->all();
    }

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
