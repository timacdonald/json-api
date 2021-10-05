<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use TiMacDonald\JsonApi\Support\Includes;

/**
 * @internal
 */
trait Relationships
{
    private string $includePrefix = '';

    public function withIncludePrefix(string $prefix): self
    {
        $this->includePrefix = "{$this->includePrefix}{$prefix}.";

        return $this;
    }

    private function nestedIncluded(Request $request): Collection
    {
        return $this->requestedRelationships($request)
            ->flatMap(fn (JsonApiResource | JsonApiResourceCollection $resource, string $key): array => $resource->withIncluded($request));
    }

    private function requestedRelationshipsAsIdentifiers(Request $request): Collection
    {
        return $this->requestedRelationships($request)
            ->map(fn (JsonApiResource | JsonApiResourceCollection $resource): array => $resource->toRelationshipIdentifier($request));
    }

    public function toRelationshipIdentifier(Request $request): array
    {
        return [
            'data' => [
                'id' => $this->toId($request),
                'type' => $this->toType($request),
            ],
        ];
    }

    private function requestedRelationships(Request $request): Collection
    {
        return once(fn () => Collection::make($this->toRelationships($request))
            ->only(Includes::parse($request, $this->includePrefix))
            ->map(fn (mixed $value, string $key): JsonApiResource | JsonApiResourceCollection => $value($request)->withIncludePrefix($key))
            ->each(function (JsonApiResource | JsonApiResourceCollection $resource) use ($request): void {
                if ($resource instanceof JsonApiResource) {
                    return;
                }

                $resource->collection = $resource->collection->uniqueStrict(fn (JsonApiResource $resource) => $resource->toRelationshipIdentifier($request));
            }));
    }
}
