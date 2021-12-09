<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use TiMacDonald\JsonApi\Exceptions\ResourceIdentificationException;
use TiMacDonald\JsonApi\Support\Cache;
use function property_exists;

abstract class JsonApiResource extends JsonResource
{
    use Concerns\Attributes;
    use Concerns\Relationships;
    use Concerns\Identification;

    public static function resolveIdUsing(Closure $resolver): void
    {
        self::$idResolver = $resolver;
    }

    public static function resolveTypeUsing(Closure $resolver): void
    {
        self::$typeResolver = $resolver;
    }

    public static function minimalAttributes(): void
    {
        self::$minimalAttributes = true;
    }

    protected function toAttributes(Request $request): array
    {
        return [
            //
        ];
    }

    protected function toRelationships(Request $request): array
    {
        return [
            //
        ];
    }

    protected function toLinks(Request $request): array
    {
        return [
            //
        ];
    }

    protected function toMeta(Request $request): array
    {
        return [
            //
        ];
    }

    protected function toId(Request $request): string
    {
        return (self::$idResolver ??= static function ($resource): string {
            if (! $resource instanceof Model) {
                throw ResourceIdentificationException::attemptingToDetermineIdFor($resource);
            }

            return (string) $resource->getKey();
        })($this->resource, $request);
    }

    protected function toType(Request $request): string
    {
        return (self::$typeResolver ??= static function ($resource): string {
            if (! $resource instanceof Model) {
                throw ResourceIdentificationException::attemptingToDetermineTypeFor($resource);
            }

            return Str::camel($resource->getTable());
        })($this->resource, $request);
    }

    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resolveId($request),
            'type' => $this->resolveType($request),
            'attributes' => (object) $this->requestedAttributes($request)->all(),
            'relationships' => (object) $this->requestedRelationshipsAsIdentifiers($request)->all(),
            'meta' => (object) $this->toMeta($request),
            'links' => (object) $this->toLinks($request),
        ];
    }

    /**
     * @param Request $request
     */
    public function with($request): array
    {
        return [
            'included' => $this->included($request),
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
