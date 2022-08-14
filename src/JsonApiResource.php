<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

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
     * @api
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toAttributes($request)
    {
        return [
            //
        ];
    }

    /**
     * @api
     *
     * @param Request $request
     * @return array<string, callable>
     */
    public function toRelationships($request)
    {
        return [
            //
        ];
    }

    /**
     * @api
     *
     * @param Request $request
     * @return array<int|string, Link|string>
     */
    public function toLinks($request)
    {
        return [
            //
        ];
    }

    /**
     * @api
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toMeta($request)
    {
        return [
            //
        ];
    }

    /**
     * @api
     *
     * @param Request $request
     * @return string
     */
    public function toId($request)
    {
        return self::idResolver()($this->resource, $request);
    }

    /**
    * @api
     *
     * @param Request $request
     * @return string
     */
    public function toType($request)
    {
        return self::typeResolver()($this->resource, $request);
    }

    /**
     * @api
     *
     * @param Request $request
     * @return ResourceLinkage
     */
    public function toResourceLink($request)
    {
        if ($this->resource === null) {
            return new ResourceLinkage(null);
        }

        return new ResourceLinkage($this->resolveResourceIdentifier($request));
    }

    /**
     * @api
     *
     * @param Request $request
     * @return ResourceIdentifier
     */
    public function toResourceIdentifier($request)
    {
        return new ResourceIdentifier($this->resolveId($request), $this->resolveType($request));
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
     * @api
     *
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
     * @api
     *
     * @param mixed $resource
     * @return JsonApiResourceCollection<mixed>
     */
    public function newCollection($resource)
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
        return tap(parent::toResponse($request)->header('Content-type', 'application/vnd.api+json'), fn () => Cache::flush($this));
    }
}
