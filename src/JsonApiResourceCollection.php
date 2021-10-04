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
        $includes = $this->collection
            ->map(fn (JsonApiResource $resource) => $resource->with($request))
            ->pluck('included')
            ->flatten()
            ->reject(fn (?JsonApiResource $resource) => $resource === null)
            ->unique(fn (JsonApiResource $resource) => $resource->toRelationshipIdentifier($request));

        // TODO Pagination
        if ($includes->isEmpty()) {
            return [];
        }

        return ['included' => $includes];
    }

    public function withIncludePrefix(string $prefix): self
    {
        $this->collection->each(fn (JsonApiResource $resource) => $resource->withIncludePrefix($prefix));

        return $this;
    }

    public function includes(Request $request): Collection
    {
        return $this->collection->map(fn (JsonApiResource $resource) => $resource->includes($request));
    }

    public function toRelationshipIdentifier(Request $request): array
    {
        return $this->collection->map(fn (JsonApiResource $resource) => $resource->toRelationshipIdentifier($request))->all();
    }
}
