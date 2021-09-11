<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use TiMacDonald\JsonApi\Support\Includes;

trait Relationships
{
    private string $includePrefix = '';

    public function withIncludePrefix(string $prefix): self
    {
        $this->includePrefix = Str::finish($prefix, '.');

        return $this;
    }

    /**
     * @return array<string, JsonApiResource>
     */
    public function resolveNestedIncludes(Request $request): array
    {
        $includes = $this->parseIncludes($request);

        $nested = $includes->flatMap(function (JsonApiResource | JsonApiResourceCollection $resource, string $key) use ($request): array {
            return $resource->withIncludePrefix("{$this->includePrefix}{$key}")->resolveNestedIncludes($request);
        });

        return $includes
            ->map(function (JsonApiResource | JsonApiResourceCollection $include): JsonApiResource | Collection {
                return $include instanceof JsonApiResource ? $include : $include->collection;
            })
            ->merge($nested)
            ->values()
            ->flatten()
            ->all();
    }

    /**
     * @return array{data: array{id: string, type: string}}
     */
    public function toRelationshipIdentifier(): array
    {
        return [
            'data' => [
                'id' => self::resourceId($this->resource),
                'type' => self::resourceType($this->resource),
            ],
        ];
    }

    /**
     * @return array<string, array{data: array{id: string, type: string}}>
     */
    private function parseRelationships(Request $request): array
    {
        return $this->parseIncludes($request)
            ->map(function (JsonApiResource | JsonApiResourceCollection $resource): array {
                return $resource->toRelationshipIdentifier();
            })
            ->all();
    }

    /**
     * @return Collection<string, JsonApiResource | JsonApiResourceCollection>
     */
    private function parseIncludes(Request $request): Collection
    {
        return Collection::make($this->toRelationships($request))
            ->only(Includes::parse($request, $this->includePrefix))
            ->map(fn (mixed $value): JsonApiResource | JsonApiResourceCollection => $value($request));
    }
}
