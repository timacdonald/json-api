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
use TiMacDonald\JsonApi\RelationshipObject;
use TiMacDonald\JsonApi\Support\Includes;
use Traversable;

trait Relationships
{
    /**
     * @internal
     */
    private string $includePrefix = '';

    /**
     * @internal
     *
     * @return $this
     */
    public function withIncludePrefix(string $prefix)
    {
        $this->includePrefix = "{$this->includePrefix}{$prefix}.";

        return $this;
    }

    /**
     * @internal
     *
     * @return Collection<int, JsonApiResource>
     */
    public function included(Request $request)
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
     *
     * @return Collection<int, JsonApiResource>
     */
    private function nestedIncluded(Request $request)
    {
        return $this->requestedRelationships($request)
            ->flatMap(fn (JsonApiResource|JsonApiResourceCollection $resource, string $key): Collection => $resource->included($request));
    }

    /**
     * @internal
     *
     * @return Collection<string, RelationshipObject>
     */
    private function requestedRelationshipsAsIdentifiers(Request $request)
    {
        return $this->requestedRelationships($request)
            ->map(fn (JsonApiResource|JsonApiResourceCollection $resource): RelationshipObject => $resource->resolveRelationshipLink($request));
    }

    /**
     * @internal
     *
     * @return Collection<string, JsonApiResource|JsonApiResourceCollection>
     */
    private function requestedRelationships(Request $request)
    {
        return $this->rememberRequestRelationships(fn (): Collection => $this->resolveRelationships($request)
            ->only($this->requestedIncludes($request))
            ->map(fn (callable $value, string $prefix): null|JsonApiResource|JsonApiResourceCollection => with($value(), fn ($resource) => match (true) {
                $resource instanceof PotentiallyMissing && $resource->isMissing() => null,
                $resource instanceof JsonApiResource || $resource instanceof JsonApiResourceCollection => $resource->withIncludePrefix($prefix),
                default => throw UnknownRelationshipException::from($resource),
            }))
            ->reject(fn (JsonApiResource|JsonApiResourceCollection|null $resource): bool => $resource === null));
    }

    /**
     * @internal
     *
     * @return Collection<string, Closure(): JsonApiResource|JsonApiResourceCollection>
     */
    private function resolveRelationships(Request $request)
    {
        return Collection::make($this->relationships ?? [])
            ->map(fn (string $class, string $relation): Closure => function () use ($class, $relation): JsonApiResource|JsonApiResourceCollection {
                return with($this->resource->{$relation}, function ($resource) use ($class): JsonApiResource|JsonApiResourceCollection {
                    if ($resource instanceof Traversable || (is_array($resource) && array_is_list($resource))) {
                        return $class::collection($resource);
                    }

                    return $class::make($resource);
                });
            })->merge($this->toRelationships($request));
    }

    /**
     * @internal
     *
     * @return array<int, string>
     */
    private function requestedIncludes(Request $request)
    {
        return Includes::getInstance()->forPrefix($request, $this->includePrefix);
    }

    /**
     * @internal
     *
     * @return $this
     */
    private function includable()
    {
        return $this;
    }

    /**
     * @internal
     *
     * @return bool
     */
    private function shouldBePresentInIncludes()
    {
        return $this->resource !== null;
    }
}
