<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Closure;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
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
    private string $includePrefix = '';

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
     * @return array<string, mixed>
     */
    protected function toRelationships(Request $request): array
    {
        return [
            //
        ];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return array{
     *      id: string,
     *      type: string,
     *      attributes: array<string, mixed>,
     *      relationships: array<string, array{data: array{id: string, type: string}}>
     * }
     */
    public function toArray($request): array
    {
        return [
            'id' => self::resourceId($this->resource),
            'type' => self::resourceType($this->resource),
            'attributes' => $this->parseAttributes($request)->all(),
            'relationships' => $this->parseRelationships($request)->all(),
        ];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return array{included?: array<mixed>}
     */
    public function with($request): array
    {
        $includes  = $this->resolveNestedIncludes($request);

        if ($includes->isEmpty()) {
            return [];
        }

        return [
            'included' => $includes->values()->all()
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<string, array{data: array{id: string, type: string}}>
     */
    private function parseRelationships(Request $request): Collection
    {
        return $this->parseIncludes($request)
            ->map(fn (JsonApiResource $resource): array => [
                'data' => self::toResourceIdentifier($resource->resource),
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<string, mixed>
     */
    private function parseIncludes(Request $request): Collection
    {
        return collect($this->toRelationships($request))
            ->only($this->requestRelationships($request))
            ->map(fn ($value, $key) => $value($request));
    }

    /**
     * @return \Illuminate\Support\Collection<string, mixed>
     */
    private function resolveNestedIncludes(Request $request): Collection
    {
        $includes = $this->parseIncludes($request);

        return $includes->merge(
            $includes->flatMap(function (JsonApiResource $resource, string $key) use ($request) {
                return $resource->withIncludePrefix($this->nestedIncludePrefix($key))->resolveNestedIncludes($request);
            })
        );
    }

    /**
     * @return array<string>
     */
    private function requestRelationships(Request $request): array
    {
        $includes = $request->query('include') ?? '';

        if (is_array($includes)) {
            throw new HttpException(400, 'The include parameter must be a comma seperated list of relationship paths.');
        }

        $includes = explode(',', $includes);

        return collect($includes)
            ->mapInto(Stringable::class)
            ->when($this->hasIncludePrefix(), function (Collection $includes): Collection {
                return $includes->filter(fn (Stringable $include) => $include->startsWith($this->includePrefix));
            })
            ->map(fn (Stringable $include) => (string) $include->after($this->includePrefix)->before('.'))
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<string, mixed>
     */
    private function parseAttributes(Request $request): Collection
    {
        return collect($this->toAttributes($request))
            ->only($this->requestedAttributes($request))
            ->map(fn ($value) => value($value, $request));
    }

    /**
     * @return ?array<string>
     */
    private function requestedAttributes(Request $request): ?array
    {
        $typeFields = $request->query('fields') ?? [];

        if (is_string($typeFields)) {
            throw new HttpException(400, 'The fields parameter must be an array of resource types.');
        }

        if (! array_key_exists(self::resourceType($this->resource), $typeFields)) {
            return null;
        }

        $fields = $typeFields[self::resourceType($this->resource)];

        if ($fields === null) {
            return [];
        }

        if (! is_string($fields)) {
            throw new HttpException(400, 'The type fields parameter must be a comma seperated list of attributes.');
        }

        return explode(',', $fields);
    }

    /**
     * @return string
     */
    private static function resourceId(mixed $resource): string
    {
        return app(ResourceIdResolver::class)($resource);
    }

    /**
     * @return string
     */
    private static function resourceType(mixed $resource): string
    {
        return app(ResourceTypeResolver::class)($resource);
    }

    /**
     * @return array{id: string, type: string}
     */
    private static function toResourceIdentifier(mixed $resource): array
    {
        return [
            'id' => self::resourceId($resource),
            'type' => self::resourceType($resource),
        ];
    }

    /**
     * @param string $prefix
     */
    private function withIncludePrefix(string $prefix): self
    {
        $this->includePrefix = Str::finish($prefix, '.');

        return $this;
    }

    /**
     * @param string $prefix
     * @return string
     */
    private function nestedIncludePrefix(string $prefix): string
    {
        return "{$this->includePrefix}{$prefix}";
    }

    private function hasIncludePrefix(): bool
    {
        return $this->includePrefix !== '';
    }
}
