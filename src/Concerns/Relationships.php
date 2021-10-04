<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use TiMacDonald\JsonApi\Support\Includes;

/**
 * @internal
 */
trait Relationships
{
    private string $includePrefix = '';

    public function withIncludePrefix(string $prefix): self
    {
        $this->includePrefix = "{$this->includePrefix}{$prefix}.";

        return $this;
    }

    /**
     * @return array<string, JsonApiResource>
     */
    public function includes(Request $request): Collection
    {
        return $this->requestedRelationships($request)
            ->map(function (JsonApiResource | JsonApiResourceCollection $include): Collection | JsonApiResource {
                return $include instanceof JsonApiResource
                    ? $include
                    : $include->collection;
            })
            ->merge($this->nestedIncludes($request))
            ->flatten();
    }

    private function nestedIncludes(Request $request)
    {
        return $this->requestedRelationships($request)
            ->flatMap(function (JsonApiResource | JsonApiResourceCollection $resource, string $key) use ($request): Collection {
                return $resource->includes($request);
            });
    }

    /**
     * @return array{data: array{id: string, type: string}}
     */
    public function toRelationshipIdentifier(Request $request): array
    {
        return [
            'data' => [
                'id' => $this->toId($request),
                'type' => $this->toType($request),
            ],
        ];
    }

    /**
     * @return Collection<string, array{data: array{id: string, type: string}}>
     */
    private function requestedRelationshipsAsIdentifiers(Request $request): Collection
    {
        return $this->requestedRelationships($request)
            ->map(fn (JsonApiResource | JsonApiResourceCollection $resource): array => $resource->toRelationshipIdentifier($request));
    }

    /**
     * @return Collection<string, JsonApiResource | JsonApiResourceCollection>
     */
    private function requestedRelationships(Request $request): Collection
    {
        return Collection::make($this->toRelationships($request))
            ->only(Includes::parse($request, $this->includePrefix))
            ->map(
                fn (mixed $value, string $key): JsonApiResource | JsonApiResourceCollection => $value($request)->withIncludePrefix($key)
            )
            ->each(function (JsonApiResource | JsonApiResourceCollection $resource) use ($request): void {
                if ($resource instanceof JsonApiResource) {
                    return;
                }

                $resource->collection = $resource->collection->unique(fn (JsonApiResource $resource) => $resource->toRelationshipIdentifier($request));
            });
    }
}
