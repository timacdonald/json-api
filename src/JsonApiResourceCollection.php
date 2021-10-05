<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class JsonApiResourceCollection extends AnonymousResourceCollection
{
    public function with($request): array
    {
        $included = $this->collection
            ->map(fn (JsonApiResource $resource): Collection => $resource->included($request))
            ->flatten()
            ->reject(fn (?JsonApiResource $resource): bool => $resource === null)
            ->uniqueStrict(fn (JsonApiResource $resource): array => $resource->toRelationshipIdentifier($request));

        if ($included->isEmpty()) {
            return [];
        }

        return ['included' => $included];
    }

    /**
     * @internal
     */
    public function withIncludePrefix(string $prefix): self
    {
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
    public function toRelationshipIdentifier(Request $request): array
    {
        return $this->collection->map(fn (JsonApiResource $resource): array => $resource->toRelationshipIdentifier($request))->all();
    }
}
