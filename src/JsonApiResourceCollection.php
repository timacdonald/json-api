<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class JsonApiResourceCollection extends AnonymousResourceCollection
{
    use Concerns\RelationshipLinks;

    /**
     * @api
     *
     * @param (callable(JsonApiResource): JsonApiResource) $callback
     * @return $this
     */
    public function map(callable $callback)
    {
        $this->collection = $this->collection->map($callback);

        return $this;
    }

    /**
     * @api
     *
     * @return RelationshipObject
     */
    public function toResourceLink(Request $request)
    {
        return RelationshipObject::toMany($this->resolveResourceIdentifiers($request)->all());
    }

    /**
     * @internal
     *
     * @return Collection<int, ResourceIdentifier>
     */
    public function resolveResourceIdentifiers(Request $request)
    {
        return $this->collection
            ->uniqueStrict(fn (JsonApiResource $resource): string => $resource->toUniqueResourceIdentifier($request))
            ->map(fn (JsonApiResource $resource): ResourceIdentifier => $resource->resolveResourceIdentifier($request));
    }

    /**
     * @api
     *
     * @param Request $request
     * @return array{included: Collection<int, JsonApiResource>, jsonapi: JsonApiServerImplementation}
     */
    public function with($request)
    {
        return [
            'included' => $this->collection
                ->map(fn (JsonApiResource $resource): Collection => $resource->included($request))
                ->flatten()
                ->uniqueStrict(fn (JsonApiResource $resource): string => $resource->toUniqueResourceIdentifier($request)),
            'jsonapi' => JsonApiResource::serverImplementationResolver()($request),
        ];
    }

    /**
     * @api
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        return tap(parent::toResponse($request)->header('Content-type', 'application/vnd.api+json'), fn () => $this->flush());
    }

    /**
     * @api
     *
     * @param Request $request
     * @param array<array-key, mixed> $paginated
     * @param array{links: array<string, ?string>} $default
     * @return array{links: array<string, string>}
     */
    public function paginationInformation($request, $paginated, $default)
    {
        return Arr::set($default, 'links', array_filter($default['links'], fn (?string $link): bool => $link !== null));
    }

    /**
     * @internal
     *
     * @return $this
     */
    public function withIncludePrefix(string $prefix)
    {
        return tap($this, function (JsonApiResourceCollection $resource) use ($prefix): void {
            $resource->collection->each(fn (JsonApiResource $resource): JsonApiResource => $resource->withIncludePrefix($prefix));
        });
    }

    /**
     * @internal
     *
     * @return Collection<int, Collection<int, JsonApiResource>>
     */
    public function included(Request $request)
    {
        return $this->collection->map(fn (JsonApiResource $resource): Collection => $resource->included($request));
    }

    /**
     * @internal
     *
     * @return Collection<int, JsonApiResource>
     */
    public function includable()
    {
        return $this->collection;
    }

    /**
     * @internal
     * @infection-ignore-all
     *
     * @return void
     */
    public function flush()
    {
        $this->collection->each(fn (JsonApiResource $resource) => $resource->flush());
    }
}
