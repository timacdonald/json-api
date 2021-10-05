<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class JsonApiResourceCollection extends AnonymousResourceCollection
{
    public function with($request): array
    {
        $included = $this->collection
            ->map(fn (JsonApiResource $resource) => $resource->withIncluded($request))
            ->flatten()
            ->reject(fn (?JsonApiResource $resource) => $resource === null)
            ->uniqueStrict(fn (JsonApiResource $resource) => $resource->toRelationshipIdentifier($request));

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
        $this->collection->each(fn (JsonApiResource $resource) => $resource->withIncludePrefix($prefix));

        return $this;
    }

    /**
     * @internal
     */
    public function withIncluded(Request $request): array
    {
        return $this->collection->map(fn (JsonApiResource $resource) => $resource->withIncluded($request))->all();
    }

    /**
     * @internal
     */
    public function toRelationshipIdentifier(Request $request): array
    {
        return $this->collection->map(fn (JsonApiResource $resource) => $resource->toRelationshipIdentifier($request))->all();
    }
}
