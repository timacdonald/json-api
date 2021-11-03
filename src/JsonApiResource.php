<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use TiMacDonald\JsonApi\Exceptions\ResourceIdentificationException;
use Closure;
use Illuminate\Http\JsonResponse;
use TiMacDonald\JsonApi\Support\Fields;
use TiMacDonald\JsonApi\Support\Includes;

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
        static::$minimalAttributes = true;
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
        return (self::$idResolver ??= static function (mixed $resource): string {
            if (! $resource instanceof Model) {
                throw ResourceIdentificationException::attemptingToDetermineIdFor($resource);
            }

            return (string) $resource->getKey();
        })($this->resource);
    }

    protected function toType(Request $request): string
    {
        return (self::$typeResolver ??= static function (mixed $resource): string {
            if (! $resource instanceof Model) {
                throw ResourceIdentificationException::attemptingToDetermineTypeFor($resource);
            }

            return Str::camel($resource->getTable());
        })($this->resource);
    }

    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->toId($request),
            'type' => $this->toType($request),
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
        $collection = new JsonApiResourceCollection($resource, static::class);

        if (property_exists(static::class, 'preserveKeys')) {
            $collection->preserveKeys = (new static([]))->preserveKeys === true;
        }

        return $collection;
    }

    /**
     * @param Request $request
     */
    public function toResponse($request): JsonResponse
    {
        $response = parent::toResponse($request)->header('Content-type', 'application/vnd.api+json');

        Includes::getInstance()->flush();

        Fields::getInstance()->flush();

        return $response;
    }
}
