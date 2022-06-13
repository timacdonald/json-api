<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Closure;
use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;

trait Caching
{
    /**
     * @internal
     */
    private ?string $idCache = null;

    /**
     * @internal
     */
    private ?string $typeCache = null;

    /**
     * @internal
     */
    private ?Collection $requestedRelationshipsCache = null;

    /**
     * @internal
     * @infection-ignore-all
     */
    public function flush(): void
    {
        $this->idCache = null;

        $this->typeCache = null;

        if ($this->requestedRelationshipsCache !== null) {
            $this->requestedRelationshipsCache->each(
                /**
                 * @param JsonApiResource|JsonApiResourceCollection $resource
                 */
                fn ($resource) => $resource->flush()
            );
        }

        $this->requestedRelationshipsCache = null;
    }

    /**
     * @internal
     * @infection-ignore-all
     */
    private function rememberId(Closure $closure): string
    {
        return $this->idCache ??= $closure();
    }


    /**
     * @internal
     * @infection-ignore-all
     */
    private function rememberType(Closure $closure): string
    {
        return $this->typeCache ??= $closure();
    }

    /**
     * @internal
     * @infection-ignore-all
     */
    private function rememberRequestRelationships(Closure $closure): Collection
    {
        return $this->requestedRelationshipsCache ??= $closure();
    }

    /**
     * @internal
     */
    public function requestedRelationshipsCache(): ?Collection
    {
        return $this->requestedRelationshipsCache;
    }
}
