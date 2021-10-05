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
        if (! $this->resource instanceof Model) {
            throw ResourceIdentificationException::attemptingToDetermineIdFor($this->resource);
        }

        return (string) $this->resource->getKey();
    }

    protected function toType(Request $request): string
    {
        if (! $this->resource instanceof Model) {
            throw ResourceIdentificationException::attemptingToDetermineTypeFor($this->resource);
        }

        return Str::camel($this->resource->getTable());
    }

    public function toArray($request): array
    {
        $payload = [
            'id' => $this->toId($request),
            'type' => $this->toType($request),
            'attributes' => (object) $this->requestedAttributes($request)->all(),
            'relationships' => (object) $this->requestedRelationshipsAsIdentifiers($request)->all(),
        ];

        $meta = $this->toMeta($request);

        if ($meta !== []) {
            $payload = array_merge($payload, ['meta' => $meta]);
        }

        $links = $this->toLinks($request);

        if ($links !== []) {
            $payload = array_merge($payload, ['links' => $links]);
        }

        return $payload;
    }

    public function with($request): array
    {
        $included = $this->included($request);

        if ($included->isNotEmpty()) {
            return ['included' => $included];
        }

        return [];
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
