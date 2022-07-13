<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\PotentiallyMissing;
use Illuminate\Support\Collection;
use RuntimeException;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
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
        $this->includePrefix = "{$this->includePrefix}{$prefix}.");

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
                fn ($resource) => $resource->resolveRelationshipLink($request)
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

                    throw new RuntimeException('Unknown relationship found. Your relationships should always return a class that extend the JsonApiResource.');
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
