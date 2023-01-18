<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
        $resourceIdentifiers = $this->collection
            ->uniqueStrict(static fn (JsonApiResource $resource): string => $resource->toUniqueResourceIdentifier($request))
            ->map(static fn (JsonApiResource $resource): ResourceIdentifier => $resource->resolveResourceIdentifier($request));

        return RelationshipObject::toMany($resourceIdentifiers->all());
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
                ->map(static fn (JsonApiResource $resource): Collection => $resource->included($request))
                ->flatten()
                ->uniqueStrict(static fn (JsonApiResource $resource): string => $resource->toUniqueResourceIdentifier($request)),
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
        $default['links'] = array_filter($default['links'], static fn (?string $link): bool => $link !== null);

        return $default;
    }

    /**
     * @internal
     *
     * @return $this
     */
    public function withIncludePrefix(string $prefix)
    {
        return tap($this, static function (JsonApiResourceCollection $resource) use ($prefix): void {
            $resource->collection->each(static fn (JsonApiResource $resource): JsonApiResource => $resource->withIncludePrefix($prefix));
        });
    }

    /**
     * @internal
     *
     * @return Collection<int, Collection<int, JsonApiResource>>
     */
    public function included(Request $request)
    {
        return $this->collection->map(static fn (JsonApiResource $resource): Collection => $resource->included($request));
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
        $this->collection->each(static fn (JsonApiResource $resource) => $resource->flush());
    }
}
