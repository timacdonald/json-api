<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Closure;
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
    private ?Collection $requestedRelationshipsCache = null;

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
            ->reject(fn (JsonApiResource | NullJsonApiResource $resource): bool => $resource instanceof NullJsonApiResource);
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
    private function requestedRelationships(Request $request): Collection
    {
        return $this->rememberRequestRelationships(fn (): Collection => Collection::make($this->toRelationships($request))
            ->only(Includes::getInstance()->parse($request, $this->includePrefix))
            ->map(function (mixed $value, string $key) use ($request): JsonApiResource | JsonApiResourceCollection | NullJsonApiResource {
                return ($value() ?? new NullJsonApiResource())->withIncludePrefix($key);
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
     * @infection-ignore-all
     */
    public function flush(): void
    {
        $this->requestedRelationshipsCache?->each(function (JsonApiResource | JsonApiResourceCollection | NullJsonApiResource $resource): void {
            $resource->flush();
        });

        $this->requestedRelationshipsCache = null;
    }

    /**
     * @internal
     * @infection-ignore-all
     */
    private function rememberRequestRelationships(Closure $closure): Collection
    {
        return $this->requestedRelationshipsCache ??= $closure();
    }

    /**
     * @internal
     */
    public function requestedRelationshipsCache(): ?Collection
    {
        return $this->requestedRelationshipsCache;
    }
}
