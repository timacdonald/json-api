<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class JsonApiResourceCollection extends AnonymousResourceCollection
{
    /**
     * @param Request $request
     *
     * @return array{included?: Collection<JsonApiResource>}
     */
    public function with($request): array
    {
        $includes = $this->collection
            ->map(static fn (JsonApiResource $resource) => $resource->with($request))
            ->pluck('included')
            ->flatten()
            ->reject(static fn (?JsonApiResource $resource) => $resource === null)
            ->unique(static fn (JsonApiResource $resource) => $resource->toRelationshipIdentifier($request));

        // TODO Pagination
        if ($includes->isEmpty()) {
            return [];
        }

        return ['included' => $includes];
    }

    /**
     * @return JsonApiResourceCollection<JsonApiResource>
     */
    public function withIncludePrefix(string $prefix): self
    {
        $this->collection->each(static fn (JsonApiResource $resource) => $resource->withIncludePrefix($prefix));

        return $this;
    }

    /**
     * @return array<array<JsonApiResource>>
     */
    public function includes(Request $request): Collection
    {
        return $this->collection->map(static fn (JsonApiResource $resource) => $resource->includes($request));
    }

    /**
     * @return array<array{data: array{id: string, type: string}}>
     */
    public function toRelationshipIdentifier(Request $request): array
    {
        return $this->collection->map(static fn (JsonApiResource $resource) => $resource->toRelationshipIdentifier($request))->all();
    }
}
