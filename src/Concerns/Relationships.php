<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\PotentiallyMissing;
use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use TiMacDonald\JsonApi\Support\Includes;
use TiMacDonald\JsonApi\Support\UnknownRelationship;

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
        return tap($this, fn (JsonApiResource $resource): string => $resource->includePrefix = "{$this->includePrefix}{$prefix}.");
    }

    /**
     * @internal
     */
    public function included(Request $request): Collection
    {
        return $this->requestedRelationships($request)
            ->map(
                /**
                 * @param JsonApiResource|JsonApiResourceCollection|UnknownRelationship $include
                 * @return Collection|JsonApiResource|UnknownRelationship
                 */
                fn ($include) => $include->includable()
            )
            ->merge($this->nestedIncluded($request))
            ->flatten()
            ->filter(
                /**
                 * @param JsonApiResource|UnknownRelationship $resource
                 */
                fn ($resource): bool => $resource->shouldBePresentInIncludes()
            )
            ->values();
    }

    /**
     * @internal
     */
    private function nestedIncluded(Request $request): Collection
    {
        return $this->requestedRelationships($request)
            ->flatMap(
                /**
                 * @param JsonApiResource|JsonApiResourceCollection|UnknownRelationship $resource
                 */
                fn ($resource, string $key): Collection => $resource->included($request)
            );
    }

    /**
     * @internal
     */
    private function requestedRelationshipsAsIdentifiers(Request $request): Collection
    {
        return $this->requestedRelationships($request)
            ->map(
                /**
                 * @param JsonApiResource|JsonApiResourceCollection|UnknownRelationship $resource
                 * @return mixed
                 */
                fn ($resource) => $resource->toResourceLink($request)
            );
    }

    /**
     * @internal
     */
    private function requestedRelationships(Request $request): Collection
    {
        return $this->rememberRequestRelationships(fn (): Collection => Collection::make($this->toRelationships($request))
            ->only(Includes::getInstance()->parse($request, $this->includePrefix))
            ->map(
                /**
                 * @return JsonApiResource|JsonApiResourceCollection|UnknownRelationship
                 */
                function (Closure $value, string $prefix) {
                    $resource = $value();

                    if ($resource instanceof JsonApiResource || $resource instanceof JsonApiResourceCollection) {
                        return $resource->withIncludePrefix($prefix);
                    }

                    return new UnknownRelationship($resource);
                }
            )->reject(
                /**
                 * @param JsonApiResource|JsonApiResourceCollection|UnknownRelationship $resource
                 */
                fn ($resource): bool => $resource instanceof PotentiallyMissing && $resource->isMissing()
            ));
    }

    /**
     * @internal
     * @infection-ignore-all
     */
    public function flush(): void
    {
        if ($this->requestedRelationshipsCache !== null) {
            $this->requestedRelationshipsCache->each(
                /**
                 * @param JsonApiResource|JsonApiResourceCollection|UnknownRelationship $resource
                 */
                fn ($resource) => $resource->flush()
            );
        }

        $this->requestedRelationshipsCache = null;

        $this->idCache = null;

        $this->typeCache = null;
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
    private function includable(): self
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
