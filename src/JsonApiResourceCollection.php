<?php

namespace TiMacDonald\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class JsonApiResourceCollection extends AnonymousResourceCollection
{
    /**
     * @param Request $request
     * @return array{included?: array<JsonApiResource>}
     */
    public function with($request): array
    {
        $includes = $this->collection
            ->map(fn (JsonApiResource $resource) => $resource->with($request))
            ->pluck('included')
            ->flatten()
            ->filter()
            ->all();

        if (count($includes) === 0) {
            return [];
        }

        return ['included' => $includes];
    }

    /**
     * @return JsonApiResourceCollection<JsonApiResource>
     */
    public function withIncludePrefix(string $prefix): self
    {
        $this->collection->each(fn (JsonApiResource $resource) => $resource->withIncludePrefix($prefix));

        return $this;
    }

    /**
     * @return array<array<JsonApiResource>>
     */
    public function resolveNestedIncludes(Request $request): array
    {
        return $this->collection->map(fn (JsonApiResource $resource) => $resource->resolveNestedIncludes($request))->all();
    }

    /**
     * @return array<array{data: array{id: string, type: string}}>
     */
    public function toRelationshipIdentifier()
    {
        return $this->collection->map(fn (JsonApiResource $resource) => $resource->toRelationshipIdentifier())->all();
    }
}
