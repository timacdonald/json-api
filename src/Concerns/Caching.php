<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Support\Collection;

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
            $this->requestedRelationshipsCache->each(fn (JsonApiResource|JsonApiResourceCollection $relation) => $relation->flush());
        }

        $this->requestedRelationshipsCache = null;

        Includes::getInstance()->flush();

        Fields::getInstance()->flush();
    }

    /**
     * @internal
     * @infection-ignore-all
     */
    private function rememberId(callable $closure): string
    {
        return $this->idCache ??= $closure();
    }

    /**
     * @internal
     * @infection-ignore-all
     */
    private function rememberType(callable $closure): string
    {
        return $this->typeCache ??= $closure();
    }

    /**
     * @internal
     * @infection-ignore-all
     */
    private function rememberRequestRelationships(callable $closure): Collection
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
