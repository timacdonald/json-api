<?php

namespace TiMacDonald\JsonApi;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TiMacDonald\JsonApi\Contracts\ResourceIdResolver;
use TiMacDonald\JsonApi\Contracts\ResourceTypeable;
use TiMacDonald\JsonApi\Contracts\ResourceTypeResolver;

class JsonApiResource extends JsonResource
{
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
     *      relationships: array<string, mixed>
     * }
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resourceId(),
            'type' => $this->resourceType(),
            'attributes' => $this->parseAttributes($request),
            'relationships' => $this->parseRelationships($request),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseRelationships(Request $request): array
    {
        return collect($this->toRelationships($request))
            ->only($this->requestRelationships($request))
            ->map(fn ($value) => $value($request))
            ->all();
    }

    /**
     * @return array<string>
     */
    protected function requestRelationships(Request $request): array
    {
        $includes = $request->query('include') ?? '';

        if (is_array($includes)) {
            throw new HttpException(400, 'The include parameter must be a comma seperated list of relationship paths.');
        }

        return explode(',', $includes);
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseAttributes(Request $request): array
    {
        return collect($this->toAttributes($request))
            ->only($this->requestedAttributes($request))
            ->map(fn ($value) => value($value, $request))
            ->all();
    }

    /**
     * @return array<string>
     */
    protected function requestedAttributes(Request $request): ?array
    {
        $typeFields = $request->query('fields') ?? [];

        if (is_string($typeFields)) {
            throw new HttpException(400, 'The fields parameter must be an array of resource types.');
        }

        if (! array_key_exists($this->resourceType(), $typeFields)) {
            return null;
        }

        $fields = $typeFields[$this->resourceType()];

        if ($fields === null) {
            return [];
        }

        if (! is_string($fields)) {
            throw new HttpException(400, 'The type fields parameter must be a comma seperated list of attributes.');
        }

        return explode(',', $fields);
    }

    protected function resourceId(): string
    {
        return app(ResourceIdResolver::class)($this->resource);
    }

    protected function resourceType(): string
    {
        return app(ResourceTypeResolver::class)($this->resource);
    }
}
