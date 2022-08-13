<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use stdClass;
use TiMacDonald\JsonApi\Support\Cache;

use function property_exists;

abstract class JsonApiResource extends JsonResource
{
    use Concerns\Attributes;
    use Concerns\Caching;
    use Concerns\Identification;
    use Concerns\Implementation;
    use Concerns\Links;
    use Concerns\Meta;
    use Concerns\Relationships;

    /**
     * @var array<Closure(ResourceIdentifier): void>
     */
    private array $resourceIdentifierCallbacks = [];

    /**
     * @var array<Closure(RelationshipLink): void>
     */
    private array $relationshipLinkCallbacks = [];

    // TODO should all these methods be public lie toArray() ?
    /**
     * @api
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    protected function toAttributes($request)
    {
        return [
            //
        ];
    }

    /**
     * @param Request $request
     * @return array<string, Closure>
     */
    protected function toRelationships($request)
    {
        return [
            //
        ];
    }

    /**
     * @param Request $request
     * @return array<int|string, Link|string>
     */
    protected function toLinks($request)
    {
        return [
            //
        ];
    }

    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    protected function toMeta($request)
    {
        return [
            //
        ];
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function toId($request)
    {
        return self::idResolver()($this->resource, $request);
    }

    /**
     * @see https://github.com/timacdonald/json-api#customising-the-resource-type
     * @see https://jsonapi.org/format/#document-resource-object-identification
     *
     * @param Request $request
     * @return string
     */
    protected function toType($request)
    {
        return self::typeResolver()($this->resource, $request);
    }

    /**
     * TODO: @see docs-link
     * TODO: naming is inconsistent: resource link vs relationship link
     * @see https://jsonapi.org/format/#document-resource-object-linkage
     */
    public function toResourceLink($request)
    {
        if ($this->resource === null) {
            return new RelationshipLink(null);
        }

        return new RelationshipLink($this->resolveResourceIdentifier($request));
    }

    /**
     * @internal
     */
    public function resolveRelationshipLink(Request $request): RelationshipLink
    {
        return tap($this->toResourceLink($request), function (RelationshipLink $link) {
            foreach ($this->relationshipLinkCallbacks as $callback) {
                $callback($link);
            }
        });
    }

    public function withRelationshipLink($callback)
    {
        $this->relationshipLinkCallbacks[] = $callback;

        return $this;
    }

    public function toResourceIdentifier($request)
    {
        return new ResourceIdentifier($this->resolveId($request), $this->resolveType($request));
    }

    /**
     * @internal
     */
    public function resolveResourceIdentifier(Request $request): ResourceIdentifier
    {
        return tap($this->toResourceIdentifier($request), function (ResourceIdentifier $identifier) {
            foreach ($this->resourceIdentifierCallbacks as $callback) {
                $callback($identifier);
            }
        });
    }

    public function withResourceIdentifier($callback)
    {
        $this->resourceIdentifierCallbacks[] = $callback;

        return $this;
    }

    /**
     * @param Request $request
     * @return array{id: string, type: string, attributes: stdClass, relationships: stdClass, meta: stdClass, links: stdClass}
     */
    public function toArray($request)
    {
        return [
            'id' => $this->resolveId($request),
            'type' => $this->resolveType($request),
            'attributes' => (object) $this->requestedAttributes($request)->all(),
            'relationships' => (object) $this->requestedRelationshipsAsIdentifiers($request)->all(),
            'meta' => (object) array_merge($this->toMeta($request), $this->meta),
            'links' => (object) self::parseLinks(array_merge($this->toLinks($request), $this->links)),
        ];
    }

    /**
     * @param Request $request
     * @return array{included: Collection, jsonapi: JsonApiServerImplementation}
     */
    public function with($request)
    {
        return [
            'included' => $this->included($request)
                ->uniqueStrict(static fn (JsonApiResource $resource): string => $resource->toUniqueResourceIdentifier($request)),
            'jsonapi' => self::serverImplementationResolver()($request),
        ];
    }

    /**
     * @param mixed $resource
     * @return JsonApiResourceCollection<mixed>
     */
    public static function collection($resource)
    {
        return tap($this->newCollection($resource), static function (JsonApiResourceCollection $collection): void {
            if (property_exists(static::class, 'preserveKeys')) {
                /** @phpstan-ignore-next-line */
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    /**
     * @param mixed $resource
     * @return JsonApiResourceCollection<mixed>
     */
    protected function newCollection($resource)
    {
        return new JsonApiResourceCollection($resource, static::class);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function toResponse($request)
    {
        return tap(parent::toResponse($request)->header('Content-type', 'application/vnd.api+json'), fn () => Cache::flush($this));
    }
}
