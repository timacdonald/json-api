<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use TiMacDonald\JsonApi\Exceptions\ResourceIdentificationException;
use function array_merge;
use function property_exists;

abstract class JsonApiResource extends JsonResource
{
    use Concerns\Attributes;
    use Concerns\Relationships;

    public static function minimalAttributes(): void
    {
        static::$minimalAttributes = true;
    }

    public static function includeAvailableAttributesViaMeta(): void
    {
        static::$includeAvailableAttributesViaMeta = true;
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

    protected function toMeta(Request $request): array
    {
        if (! self::$includeAvailableAttributesViaMeta) {
            return [
                //
            ];
        }

        return [
            'availableAttributes' => $this->availableAttributes($request),
        ];
    }

    protected function toId(Request $request): string
    {
        if ($this->resource instanceof Model) {
            return (string) $this->resource->getKey();
        }

        throw ResourceIdentificationException::attemptingToDetermineIdFor($this->resource);
    }

    protected function toType(Request $request): string
    {
        if ($this->resource instanceof Model) {
            return Str::camel($this->resource->getTable());
        }

        throw ResourceIdentificationException::attemptingToDetermineTypeFor($this->resource);
    }

    public function toArray($request): array
    {
        $toArray = [
            'id' => $this->toId($request),
            'type' => $this->toType($request),
            'attributes' => (object) $this->requestedAttributes($request)->all(),
            'relationships' => (object) $this->requestedRelationshipsAsIdentifiers($request)->all(),
        ];

        $meta = $this->toMeta($request);

        if ($meta === []) {
            return $toArray;
        }

        // TODO: links, etc...
        return array_merge($toArray, ['meta' => $meta]);
    }

    public function with($request): array
    {
        $includes = $this->includes($request);

        if ($includes->isEmpty()) {
            return [
                //
            ];
        }

        return ['included' => $includes];
    }

    public static function collection(mixed $resource): JsonApiResourceCollection
    {
        return tap(new JsonApiResourceCollection($resource, static::class), function (JsonApiResourceCollection $collection): void {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }
}
