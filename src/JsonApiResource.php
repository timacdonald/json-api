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
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request)
    {
        return [
            //
        ];
    }

    /**
     * @return array<string, (callable(): JsonApiResource|JsonApiResourceCollection|PotentiallyMissing)>
     */
    public function toRelationships(Request $request)
    {
        return [
            //
        ];
    }

    /**
     * @return array<int, Link>
     */
    public function toLinks(Request $request)
    {
        return [
            //
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toMeta(Request $request)
    {
        return [
            //
        ];
    }

    /**
     * @return string
     */
    public function toId(Request $request)
    {
        return self::idResolver()($this->resource, $request);
    }

    /**
     * @return string
     */
    public function toType(Request $request)
    {
        return self::typeResolver()($this->resource, $request);
    }

    /**
     * @return RelationshipObject
     */
    public function toResourceLink(Request $request)
    {
        return $this->resource === null
            ? RelationshipObject::toOne(null)
            : RelationshipObject::toOne($this->resolveResourceIdentifier($request));
    }

    /**
     * @return ResourceIdentifier
     */
    public function toResourceIdentifier(Request $request)
    {
        return new ResourceIdentifier($this->resolveType($request), $this->resolveId($request));
    }

    /**
     * @return ServerImplementation|null
     */
    public static function toServerImplementation(Request $request)
    {
        return self::serverImplementationResolver()($request);
    }

    /**
     * @return array{id: string, type: string, attributes?: stdClass, relationships?: stdClass, meta?: stdClass, links?: stdClass}
     */
    public function toArray($request)
    {
        return [
            'id' => $this->resolveId($request),
            'type' => $this->resolveType($request),
            ...Collection::make([
                'attributes' => $this->requestedAttributes($request)->all(),
                'relationships' => $this->requestedRelationshipsAsIdentifiers($request)->all(),
                'links' => self::parseLinks(array_merge($this->toLinks($request), $this->links)),
                'meta' => array_merge($this->toMeta($request), $this->meta),
            ])->filter()->map(fn ($value) => (object) $value),
        ];
    }

    /**
     * @param  Request  $request
     * @return array{included?: array<int, JsonApiResource>, jsonapi: ServerImplementation}
     */
    public function with($request)
    {
        return [
            ...($included = $this->included($request)
                ->uniqueStrict(fn (JsonApiResource $resource): array => $resource->uniqueKey($request))
                ->values()
                ->all()) ? ['included' => $included] : [],
            ...($implementation = self::toServerImplementation($request))
                ? ['jsonapi' => $implementation] : [],
        ];
    }

    /**
     * TODO this may be removed once all supported versions have `newCollection`.
     *
     * @return JsonApiResourceCollection<int, mixed>
     */
    public static function collection(mixed $resource)
    {
        return tap(static::newCollection($resource), function (JsonApiResourceCollection $collection): void {
            if (property_exists(static::class, 'preserveKeys')) {
                /** @phpstan-ignore-next-line */
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    /**
     * @return JsonApiResourceCollection<int, mixed>
     */
    public static function newCollection(mixed $resource)
    {
        return new JsonApiResourceCollection($resource, static::class);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function toResponse($request)
    {
        return tap(parent::toResponse($request)->header('Content-type', 'application/vnd.api+json'), $this->flush(...));
    }
}
