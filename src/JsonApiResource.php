<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\PotentiallyMissing;
use Illuminate\Support\Collection;
use stdClass;

use function property_exists;

abstract class JsonApiResource extends JsonResource
{
    use Concerns\Attributes;
    use Concerns\Caching;
    use Concerns\Identification;
    use Concerns\Implementation;
    use Concerns\Links;
    use Concerns\Meta;
    use Concerns\RelationshipLinks;
    use Concerns\Relationships;

    /**
     * @api
     *
     * @return array<string, class-string>
     */
    protected array $relationships = [
        //
    ];

    /**
     * @api
     *
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request)
    {
        return [
            //
        ];
    }

    /**
     * @api
     *
     * @TODO: callable needs type information
     * @return array<string, (callable(): JsonApiResource|JsonApiResourceCollection|PotentiallyMissing)>
     */
    public function toRelationships(Request $request)
    {
        return [
            //
        ];
    }

    /**
     * @api
     *
     * @return array<int, Link>
     */
    public function toLinks(Request $request)
    {
        return [
            //
        ];
    }

    /**
     * @api
     *
     * @return array<string, mixed>
     */
    public function toMeta(Request $request)
    {
        return [
            //
        ];
    }

    /**
     * @api
     *
     * @return string
     */
    public function toId(Request $request)
    {
        return self::idResolver()($this->resource, $request);
    }

    /**
    * @api
     *
     * @return string
     */
    public function toType(Request $request)
    {
        return self::typeResolver()($this->resource, $request);
    }

    /**
     * @api
     *
     * @return RelationshipObject
     */
    public function toResourceLink(Request $request)
    {
        return $this->resource === null
            ? RelationshipObject::toOne(null)
            : RelationshipObject::toOne($this->resolveResourceIdentifier($request));
    }

    /**
     * @api
     *
     * @return ResourceIdentifier
     */
    public function toResourceIdentifier(Request $request)
    {
        return new ResourceIdentifier($this->resolveType($request), $this->resolveId($request));
    }

    /**
     * @api
     *
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
     * @api
     *
     * @param Request $request
     * @return array{included: Collection<int, JsonApiResource>, jsonapi: JsonApiServerImplementation}
     */
    public function with($request)
    {
        return [
            'included' => $this->included($request)
                ->uniqueStrict(fn (JsonApiResource $resource): string => $resource->toUniqueResourceIdentifier($request)),
            'jsonapi' => self::serverImplementationResolver()($request),
        ];
    }

    /**
     * @api
     *
     * @param mixed $resource
     * @return JsonApiResourceCollection<int, mixed>
     */
    public static function collection($resource)
    {
        return tap(static::newCollection($resource), function (JsonApiResourceCollection $collection): void {
            if (property_exists(static::class, 'preserveKeys')) {
                /** @phpstan-ignore-next-line */
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    /**
     * @api
     *
     * @return JsonApiResourceCollection<int, mixed>
     */
    public static function newCollection(mixed $resource)
    {
        return new JsonApiResourceCollection($resource, static::class);
    }

    /**
     * @api
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function toResponse($request)
    {
        // TODO: the flush call here is triggering repeated Includes::flush() cals, because of collection.s
        return tap(parent::toResponse($request)->header('Content-type', 'application/vnd.api+json'), fn () => $this->flush());
    }
}
