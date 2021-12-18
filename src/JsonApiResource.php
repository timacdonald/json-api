<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TiMacDonald\JsonApi\Support\Cache;
use function property_exists;

abstract class JsonApiResource extends JsonResource
{
    use Concerns\Attributes;
    use Concerns\Identification;
    use Concerns\Implementation;
    use Concerns\Links;
    use Concerns\Relationships;

    /**
     * @see https://github.com/timacdonald/json-api#customising-the-resource-id
     * @see https://jsonapi.org/format/#document-resource-object-identification
     */
    public static function resolveIdUsing(Closure $resolver): void
    {
        self::$idResolver = $resolver;
    }

    /**
     * @see https://github.com/timacdonald/json-api#customising-the-resource-type
     * @see https://jsonapi.org/format/#document-resource-object-identification
     */
    public static function resolveTypeUsing(Closure $resolver): void
    {
        self::$typeResolver = $resolver;
    }

    /**
     * TODO see local-docs
     * @see https://jsonapi.org/format/#document-jsonapi-object
     */
    public static function resolveServerImplementationUsing(Closure $resolver): void
    {
        self::$serverImplementationResolver = $resolver;
    }

    /**
     * @see https://github.com/timacdonald/json-api#minimal-resource-attributes
     */
    public static function minimalAttributes(): void
    {
        self::$minimalAttributes = true;
    }

    /**
     * @see https://github.com/timacdonald/json-api#resource-attributes
     * @see https://jsonapi.org/format/#document-resource-object-attributes
     */
    protected function toAttributes(Request $request): array
    {
        return [
            // 'name' => $this->name,
            //
            // or with lazy evaluation...
            //
            // 'address' => fn () => new Address($this->address),
        ];
    }

    /**
     * @see https://github.com/timacdonald/json-api#resource-relationships
     * @see https://jsonapi.org/format/#document-resource-object-relationships
     */
    protected function toRelationships(Request $request): array
    {
        return [
            // 'posts' => fn () => PostResource::collection($this->posts),
            // 'avatar' => fn () => AvatarResource::make($this->avatar),
        ];
    }

    /**
     * @see https://github.com/timacdonald/json-api#resource-links
     * @see https://jsonapi.org/format/#document-resource-object-links
     */
    protected function toLinks(Request $request): array
    {
        return [
            // Link::self(route('users.show'), $this->resource),
            // Link::related(/** ... */),
            // 'whatever' => 'Something'
            // 'whateverElse' => new Link('whatever')
        ];
    }

    /**
     * @see https://github.com/timacdonald/json-api#resource-meta
     * @see https://jsonapi.org/format/#document-meta
     */
    protected function toMeta(Request $request): array
    {
        return [
            // 'resourceDeprecated' => false,
        ];
    }

    /**
     * @see https://github.com/timacdonald/json-api#customising-the-resource-id
     * @see https://jsonapi.org/format/#document-resource-object-identification
     */
    protected function toId(Request $request): string
    {
        return self::idResolver()($this->resource, $request);
    }

    /**
     * @see https://github.com/timacdonald/json-api#customising-the-resource-type
     * @see https://jsonapi.org/format/#document-resource-object-identification
     */
    protected function toType(Request $request): string
    {
        return self::typeResolver()($this->resource, $request);
    }

    /**
     * TODO: @see docs-link
     * @see https://jsonapi.org/format/#document-resource-object-linkage
     */
    public function toResourceLink(Request $request): RelationshipLink
    {
        return new RelationshipLink(
            new ResourceIdentifier($this->resolveId($request), $this->resolveType($request))
        );
    }

    /**
     * @return mixed
     */
    public function whenNull(Request $request, Closure $toArray)
    {
        return null;
    }

    /**
     * @param Request $request
     */
    public function toArray($request): ?array
    {
        $toArray = fn () => [
            'id' => $this->resolveId($request),
            'type' => $this->resolveType($request),
            'attributes' => (object) $this->requestedAttributes($request)->all(),
            'relationships' => (object) $this->requestedRelationshipsAsIdentifiers($request)->all(),
            'meta' => (object) $this->toMeta($request),
            'links' => (object) $this->resolveLinks($request),
        ];

        return $this->resource === null
            ? $this->whenNull($request, $toArray)
            : $toArray();
    }

    /**
     * @param Request $request
     */
    public function with($request): array
    {
        return [
            'included' => $this->included($request)
                ->uniqueStrict(fn (JsonApiResource $resource): string => $resource->toUniqueResourceIdentifier($request)),
            'jsonapi' => self::serverImplementationResolver()($request),
        ];
    }

    /**
     * @param mixed $resource
     */
    public static function collection($resource): JsonApiResourceCollection
    {
        return tap(new JsonApiResourceCollection($resource, static::class), function (JsonApiResourceCollection $collection): void {
            if (property_exists(static::class, 'preserveKeys')) {
                /** @phpstan-ignore-next-line */
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    /**
     * @param Request $request
     */
    public function toResponse($request): JsonResponse
    {
        return tap(parent::toResponse($request)->header('Content-type', 'application/vnd.api+json'), fn () => Cache::flush($this));
    }
}
