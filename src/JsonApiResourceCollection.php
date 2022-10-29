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
     * @var array<callable(RelationshipObject): void>
     */
    private $relationshipLinkCallbacks = [];

    /**
     * @api
     *
     * @param callable $callback
     * @return $this
     */
    public function map($callback)
    {
        $this->collection = $this->collection->map($callback);

        return $this;
    }

    /**
     * @api
     *
     * @param callable $callback
     * @return $this
     */
    public function withRelationshipLink($callback)
    {
        $this->relationshipLinkCallbacks[] = $callback;

        return $this;
    }

    /**
     * @api
     *
     * @param Request $request
     * @return RelationshipCollectionLink
     */
    public function toResourceLink($request)
    {
        $resourceLinks = $this->collection
            ->uniqueStrict(static fn (JsonApiResource $resource): string => $resource->toUniqueResourceIdentifier($request))
            ->map(static fn (JsonApiResource $resource): ResourceIdentifier => $resource->resolveResourceIdentifier($request));

        return new RelationshipCollectionLink($resourceLinks->all());
    }

    /**
     * @api
     *
     * @param Request $request
     * @return array{included: Collection, jsonapi: JsonApiResource}
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
        return tap(parent::toResponse($request)->header('Content-type', 'application/vnd.api+json'), fn (): mixed => $this->flush($this));
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
     * @param string $prefix
     * @return $this
     */
    public function withIncludePrefix($prefix)
    {
        return tap($this, static function (JsonApiResourceCollection $resource) use ($prefix): void {
            $resource->collection->each(static fn (JsonApiResource $resource): JsonApiResource => $resource->withIncludePrefix($prefix));
        });
    }

    /**
     * @internal
     *
     * @param Request $request
     * @return Collection
     */
    public function included($request)
    {
        return $this->collection->map(static fn (JsonApiResource $resource): Collection => $resource->included($request));
    }

    /**
     * @internal
     *
     * @param Request $request
     * @return RelationshipCollectionLink
     */
    public function resolveRelationshipLink($request)
    {
        return tap($this->toResourceLink($request), function (RelationshipCollectionLink $link): void {
            foreach ($this->relationshipLinkCallbacks as $callback) {
                $callback($link);
            }
        });
    }

    /**
     * @internal
     *
     * @return Collection
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
        $this->collection->each(static fn (JsonApiResource $resource): void => $resource->flush());
    }
}
