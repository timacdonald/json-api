<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\Support\Cache;

class JsonApiResourceCollection extends AnonymousResourceCollection
{
    /**
     * @param Request $request
     */
    public function with($request): array
    {
        return [
            'included' => $this->collection
                ->map(fn (JsonApiResource $resource): Collection => $resource->included($request))
                ->flatten()
                ->reject(fn (?JsonApiResource $resource): bool => $resource === null)
                ->uniqueStrict(fn (JsonApiResource $resource): string => $resource->toUniqueResourceIdentifier($request)),
            'jsonapi' => JsonApiResource::serverImplementationResolver()($request),
        ];
    }

    /**
     * @param Request $request
     */
    public function toResponse($request)
    {
        return tap(parent::toResponse($request)->header('Content-type', 'application/vnd.api+json'), fn () => Cache::flush($this));
    }

    /**
     * @internal
     */
    public function withIncludePrefix(string $prefix): self
    {
        /** @phpstan-ignore-next-line */
        $this->collection->each(fn (JsonApiResource $resource): JsonApiResource => $resource->withIncludePrefix($prefix));

        return $this;
    }

    /**
     * @internal
     */
    public function included(Request $request): Collection
    {
        return $this->collection->map(fn (JsonApiResource $resource): Collection => $resource->included($request));
    }

    /**
     * @internal
     */
    public function asRelationship(Request $request): Collection
    {
        return $this->collection->map(fn (JsonApiResource $resource): Relationship => $resource->asRelationship($request));
    }

    /**
     * @internal
     */
    public function includable(): Collection
    {
        return $this->collection;
    }

    /**
     * @internal
     */
    public function filterDuplicates(Request $request): self
    {
        $this->collection = $this->collection->uniqueStrict(fn (JsonApiResource $resource): string => $resource->toUniqueResourceIdentifier($request));

        return $this;
    }

    /**
     * @internal
     * @infection-ignore-all
     */
    public function flush(): void
    {
        $this->collection->each(fn (JsonApiResource $resource) => $resource->flush());
    }

    /**
     * @internal
     */
    public function initialiseAsRelationship(Request $request, string $prefix): self
    {
        return $this->withIncludePrefix($prefix)->filterDuplicates($request);
    }
}
