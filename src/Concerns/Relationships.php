<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\PotentiallyMissing;
use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\Exceptions\UnknownRelationshipException;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use TiMacDonald\JsonApi\RelationshipCollectionLink;
use TiMacDonald\JsonApi\RelationshipLink;
use TiMacDonald\JsonApi\Support\Includes;

trait Relationships
{
    /**
     * @internal
     */
    private string $includePrefix = '';

    /**
     * @internal
     */
    public function withIncludePrefix(string $prefix): static
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
            ->map(fn (JsonApiResource|JsonApiResourceCollection $include): Collection|JsonApiResource => $include->includable())
            ->merge($this->nestedIncluded($request))
            ->flatten()
            ->filter(fn (JsonApiResource $resource): bool => $resource->shouldBePresentInIncludes())
            ->values();
    }

    /**
     * @internal
     */
    private function nestedIncluded(Request $request): Collection
    {
        return $this->requestedRelationships($request)
            ->flatMap(fn (JsonApiResource|JsonApiResourceCollection $resource, string $key): Collection => $resource->included($request));
    }

    /**
     * @internal
     */
    private function requestedRelationshipsAsIdentifiers(Request $request): Collection
    {
        return $this->requestedRelationships($request)
            ->map(fn (JsonApiResource|JsonApiResourceCollection $resource): RelationshipLink|RelationshipCollectionLink => $resource->resolveRelationshipLink($request));
    }

    /**
     * @internal
     */
    private function requestedRelationships(Request $request): Collection
    {
        return $this->rememberRequestRelationships(fn (): Collection => Collection::make($this->toRelationships($request))
            ->only(Includes::getInstance()->parse($request, $this->includePrefix))
            ->map(function (Closure $value, string $prefix): null|JsonApiResource|JsonApiResourceCollection {
                $resource = $value();

                if ($resource instanceof PotentiallyMissing && $resource->isMissing()) {
                    return null;
                }

                if ($resource instanceof JsonApiResource || $resource instanceof JsonApiResourceCollection) {
                    return $resource->withIncludePrefix($prefix);
                }

                throw UnknownRelationshipException::from($resource);
            })->reject(fn (JsonApiResource|JsonApiResourceCollection $resource): bool => $resource === null));
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
        return $this->resource !== null;
    }
}
