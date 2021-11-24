<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use TiMacDonald\JsonApi\Support\Includes;
use TiMacDonald\JsonApi\UnknownRelationship;

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
            ->map(function (JsonApiResource | JsonApiResourceCollection | UnknownRelationship $include): Collection | JsonApiResource | UnknownRelationship {
                return $include->includable();
            })
            ->merge($this->nestedIncluded($request))
            ->flatten()
            ->filter(fn (JsonApiResource | UnknownRelationship $resource): bool => $resource->shouldBePresentInIncludes())
            ->values();
    }

    /**
     * @internal
     */
    private function nestedIncluded(Request $request): Collection
    {
        return $this->requestedRelationships($request)
            ->flatMap(fn (JsonApiResource | JsonApiResourceCollection | UnknownRelationship $resource, string $key): Collection => $resource->included($request));
    }

    /**
     * @internal
     */
    private function requestedRelationshipsAsIdentifiers(Request $request): Collection
    {
        return $this->requestedRelationships($request)
            ->map(fn (JsonApiResource | JsonApiResourceCollection | UnknownRelationship $resource): mixed => $resource->toResourceIdentifier($request));
    }

    /**
     * @internal
     */
    private function requestedRelationships(Request $request): Collection
    {
        return $this->rememberRequestRelationships(fn (): Collection => Collection::make($this->toRelationships($request))
            ->only(Includes::getInstance()->parse($request, $this->includePrefix))
            ->map(function (Closure $value, string $key) use ($request): JsonApiResource | JsonApiResourceCollection | UnknownRelationship {
                $resource = $value();

                if ($resource instanceof JsonApiResource) {
                    return $resource->withIncludePrefix($key);
                }

                if ($resource instanceof JsonApiResourceCollection) {
                    return $resource->filterDuplicates($request)->withIncludePrefix($key);
                }

                return new UnknownRelationship($resource);
            }));
    }

    /**
     * @internal
     * @infection-ignore-all
     */
    public function flush(): void
    {
        $this->requestedRelationshipsCache?->each(function (JsonApiResource | JsonApiResourceCollection | UnknownRelationship $resource): void {
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

    /**
     * @internal
     */
    private function includable(): static
    {
        return $this;
    }

    /**
     * @internal
     */
    private function shouldBePresentInIncludes(): bool
    {
        return true;
    }
}
