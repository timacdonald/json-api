<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use function array_merge;
use Closure;
use function count;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use function property_exists;
use stdClass;
use function tap;
use TiMacDonald\JsonApi\Exceptions\ResourceIdentificationException;

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

    /**
     * @return array<string, mixed>
     */
    protected function toAttributes(Request $request): array
    {
        return [
            //
        ];
    }

    /**
     * @return array<string, Closure(Request $request): JsonApiResource>
     */
    protected function toRelationships(Request $request): array
    {
        return [
            //
        ];
    }

    /**
     * @return array{availableAttributes?: array<string>}
     */
    protected function toMeta(Request $request): array
    {
        if (self::$includeAvailableAttributesViaMeta) {
            return [
                'availableAttributes' => $this->availableAttributes($request),
            ];
        }

        return [];
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

    /**
     * @param Request $request
     *
     * @return array{
     *                id: string,
     *                type: string,
     *                attributes: stdClass,
     *                relationships: stdClass,
     *                meta?: array{availableAttributes?: array<string>}
     *                }
     */
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

    /**
     * @param Request $request
     *
     * @return array{included?: array<JsonApiResource>}
     */
    public function with($request): array
    {
        $includes = $this->includes($request);

        if (count($includes) > 0) {
            return ['included' => $includes->all()];
        }

        return [];
    }

    /**
     * @return JsonApiResourceCollection<JsonApiResource>
     */
    public static function collection(mixed $resource): JsonApiResourceCollection
    {
        return tap(new JsonApiResourceCollection($resource, static::class), static function (JsonApiResourceCollection $collection): void {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true; // @phpstan-ignore-line
            }
        });
    }
}
