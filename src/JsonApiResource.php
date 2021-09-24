<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Closure;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TiMacDonald\JsonApi\Contracts\ResourceIdResolver;
use TiMacDonald\JsonApi\Contracts\ResourceTypeable;
use TiMacDonald\JsonApi\Contracts\ResourceTypeResolver;

abstract class JsonApiResource extends JsonResource
{
    use Concerns\Attributes;
    use Concerns\Relationships;
    use Concerns\ResourceIdentification;

    public static function minimalAttributes(): void
    {
        static::$minimalAttributes = true;
    }

    public static function maximalAttributes(): void
    {
        static::$minimalAttributes = false;
    }

    public static function includeAvailableAttributesViaMeta(): void
    {
        static::$includeAvailableAttributesViaMeta = true;
    }

    public static function excludeAvailableAttributesViaMeta(): void
    {
        static::$includeAvailableAttributesViaMeta = false;
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
     * @param Request $request
     * @return array{
     *      id: string,
     *      type: string,
     *      attributes: array<string, mixed>,
     *      relationships: array<string, array{data: array{id: string, type: string}}>,
     *      meta?: array{availableAttributes?: array<string>}
     * }
     */
    public function toArray($request): array
    {
        $toArray = [
            'id' => self::resourceId($this->resource),
            'type' => self::resourceType($this->resource),
            'attributes' => (object) $this->parseAttributes($request),
            'relationships' => (object) $this->parseRelationships($request),
        ];

        $meta = $this->toMeta($request);

        if ($meta !== []) {
            $toArray = array_merge($toArray, ['meta' => $meta]);
        }

        return $toArray;
    }

    /**
     * @param Request $request
     * @return array{included?: array<JsonApiResource>}
     */
    public function with($request): array
    {
        $includes = $this->resolveNestedIncludes($request);

        if (count($includes) > 0) {
            return ['included' => $includes];
        }

        return [];
    }

    /**
     * @return array{availableAttributes?: array<string>}
     */
    private function toMeta(Request $request): array
    {
        if (self::$includeAvailableAttributesViaMeta) {
            return [
                'availableAttributes' => $this->availableAttributes($request),
            ];
        }

        return [];
    }

    /**
     * @return JsonApiResourceCollection<JsonApiResource>
     */
    public static function collection(mixed $resource): JsonApiResourceCollection
    {
        return tap(new JsonApiResourceCollection($resource, static::class), function (JsonApiResourceCollection $collection): void {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true; // @phpstan-ignore-line
            }
        });
    }
}
