<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\PotentiallyMissing;
use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\Exceptions\UnknownRelationshipException;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use TiMacDonald\JsonApi\RelationshipCollectionLink;
use TiMacDonald\JsonApi\RelationshipObject;
use TiMacDonald\JsonApi\Support\Includes;

trait Relationships
{
    /**
     * @internal
     *
     * @var string
     */
    private $includePrefix = '';

    /**
     * @internal
     *
     * @var array<callable(RelationshipObject): void>
     */
    private $relationshipLinkCallbacks = [];

    /**
     * @api
     *
     * @param callable(RelationshipObject): void $callback
     * @return $this
     */
    public function withRelationshipLink($callback)
    {
        $this->relationshipLinkCallbacks[] = $callback;

        return $this;
    }

    /**
     * @internal
     *
     * @param string $prefix
     * @return $this
     */
    public function withIncludePrefix($prefix)
    {
        $this->includePrefix = "{$this->includePrefix}{$prefix}.";

        return $this;
    }

    /**
     * @internal
     *
     * @param Request $request
     * @return Collection<int, JsonApiResource>
     */
    public function included($request)
    {
        return $this->requestedRelationships($request)
            ->map(
                fn (JsonApiResource|JsonApiResourceCollection $include): Collection|JsonApiResource => $include->includable()
            )
            ->merge($this->nestedIncluded($request))
            ->flatten()
            ->filter(fn (JsonApiResource $resource): bool => $resource->shouldBePresentInIncludes())
            ->values();
    }

    /**
     * @internal
     *
     * @param Request $request
     * @return Collection
     */
    private function nestedIncluded($request)
    {
        return $this->requestedRelationships($request)
            ->flatMap(
                fn (JsonApiResource|JsonApiResourceCollection $resource, string $key): Collection => $resource->included($request)
            );
    }

    /**
     * @internal
     *
     * @param Request $request
     * @return Collection
     */
    private function requestedRelationshipsAsIdentifiers($request)
    {
        return $this->requestedRelationships($request)
            ->map(
                fn (JsonApiResource|JsonApiResourceCollection $resource): RelationshipObject|RelationshipCollectionLink => $resource->resolveRelationshipLink($request)
            );
    }

    /**
     * @internal
     *
     * @param Request $request
     * @return Collection<string, null|JsonApiResource|JsonApiResourceCollection>
     */
    private function requestedRelationships($request)
    {
        return $this->rememberRequestRelationships(fn (): Collection => Collection::make($this->toRelationships($request))
            ->only($this->requestedIncludes($request))
            ->map(function (callable $value, string $prefix): null|JsonApiResource|JsonApiResourceCollection {
                $resource = $value();

                if ($resource instanceof PotentiallyMissing && $resource->isMissing()) {
                    return null;
                }

                if ($resource instanceof JsonApiResource || $resource instanceof JsonApiResourceCollection) {
                    return $resource->withIncludePrefix($prefix);
                }

                throw UnknownRelationshipException::from($resource);
            })->reject(fn (JsonApiResource|JsonApiResourceCollection|null $resource): bool => $resource === null));
    }

    /**
     * @internal
     *
     * @param Request $request
     * @return array<int, string>
     */
    private function requestedIncludes($request)
    {
        return Includes::getInstance()->parse($request, $this->includePrefix);
    }

    /**
     * @internal
     *
     * @param Request $request
     * @return RelationshipObject
     */
    public function resolveRelationshipLink($request)
    {
        return tap($this->toResourceLink($request), function (RelationshipObject $link): void {
            foreach ($this->relationshipLinkCallbacks as $callback) {
                $callback($link);
            }
        });
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
