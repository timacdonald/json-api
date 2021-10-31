<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use TiMacDonald\JsonApi\NullJsonApiResource;
use TiMacDonald\JsonApi\Support\Includes;

/**
 * @internal
 */
trait Relationships
{
    /**
     * @internal
     */
    private string $includePrefix = '';

    /**
     * @internal
     */
    public function withIncludePrefix(string $prefix): self
    {
        $this->includePrefix = "{$this->includePrefix}{$prefix}.";

        return $this;
    }

    /**
     * @internal
     */
    public function included(Request $request): Collection
    {
        return $this->requestedRelationships($request)
            ->map(function (JsonApiResource | JsonApiResourceCollection | NullJsonApiResource $include): Collection | JsonApiResource | NullJsonApiResource {
                return $include instanceof JsonApiResourceCollection
                    ? $include->collection
                    : $include;
            })
            ->merge($this->nestedIncluded($request))
            ->flatten()
            ->reject(fn (JsonApiResource | NullJsonApiResource $resource) => $resource instanceof NullJsonApiResource) ;
    }

    /**
     * @internal
     */
    private function nestedIncluded(Request $request): Collection
    {
        return $this->requestedRelationships($request)
            ->flatMap(fn (JsonApiResource | JsonApiResourceCollection | NullJsonApiResource $resource, string $key): Collection => $resource->included($request));
    }

    /**
     * @internal
     */
    private function requestedRelationshipsAsIdentifiers(Request $request): Collection
    {
        return $this->requestedRelationships($request)
            ->map(fn (JsonApiResource | JsonApiResourceCollection | NullJsonApiResource $resource): ?array => $resource->toResourceIdentifier($request));
    }

    /**
     * @internal
     */
    public function toResourceIdentifier(Request $request): array
    {
        return [
            'data' => [
                'id' => $this->toId($request),
                'type' => $this->toType($request),
            ],
        ];
    }

    /**
     * @internal
     */
    public function toUniqueResourceIdentifier(Request $request): string
    {
        return "type:{$this->toType($request)} id:{$this->toId($request)}";
    }

    /**
     * @internal
     */
    private function requestedRelationships(Request $request): Collection
    {
        return once(fn (): Collection => Collection::make($this->resolveRelationships($request))
            ->only(Includes::parse($request, $this->includePrefix))
            ->map(function (mixed $value, string $key) use ($request): JsonApiResource | JsonApiResourceCollection | NullJsonApiResource {
                return ($value($request) ?? new NullJsonApiResource())->withIncludePrefix($key);
            })
            ->each(function (JsonApiResource | JsonApiResourceCollection | NullJsonApiResource $resource) use ($request): void {
                if (! $resource instanceof JsonApiResourceCollection) {
                    return;
                }

                $resource->collection = $resource->collection->uniqueStrict(fn (JsonApiResource $resource): string => $resource->toUniqueResourceIdentifier($request));
            }));
    }

    /**
     * @internal
     */
    private function resolveRelationships(Request $request): array
    {
        return once(fn () => $this->toRelationships($request));
    }
}
