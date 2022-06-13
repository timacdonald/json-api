<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\PotentiallyMissing;
use Illuminate\Support\Collection;
use RuntimeException;
use TiMacDonald\JsonApi\Contracts\AdHocJsonApiResource;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use TiMacDonald\JsonApi\Support\Includes;
use TiMacDonald\JsonApi\Support\NullRelationship;

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
                 * @param JsonApiResource|JsonApiResourceCollection $include
                 * @return Collection|JsonApiResource
                 */
                fn ($include) => $include->includable()
            )
            ->merge($this->nestedIncluded($request))
            ->flatten()
            ->filter(
                /**
                 * @param JsonApiResource $resource
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
                 * @param JsonApiResource|JsonApiResourceCollection $resource
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
                 * @param JsonApiResource|JsonApiResourceCollection $resource
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
                 * @return JsonApiResource|JsonApiResourceCollection|null
                 */
                function (Closure $value, string $prefix) {
                    $resource = $value();

                    if ($resource instanceof PotentiallyMissing && $resource->isMissing()) {
                        return null;
                    }

                    if ($resource instanceof JsonApiResource || $resource instanceof JsonApiResourceCollection) {
                        return $resource->withIncludePrefix($prefix);
                    }

                    if ($resource === null) {
                        return new NullRelationship();
                    }

                    throw new RuntimeException('Unknown relationship found. Your relationships must extend the JsonApiResource class.');
                }
            )->reject(
                /**
                 * @param JsonApiResource|JsonApiResourceCollection|null $resource
                 */
                fn ($resource): bool => $resource === null
            ));
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
