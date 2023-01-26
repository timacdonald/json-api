<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;

trait Caching
{
    /**
     * @internal
     */
    private string|null $idCache = null;

    /**
     * @internal
     */
    private string|null $typeCache = null;

    /**
     * @internal
     *
     * @var Collection<string, JsonApiResource|JsonApiResourceCollection>|null
     */
    private Collection|null $requestedRelationshipsCache = null;

    /**
     * @internal
     * @infection-ignore-all
     *
     * @return void
     */
    public function flush()
    {
        $this->idCache = null;

        $this->typeCache = null;

        if ($this->requestedRelationshipsCache !== null) {
            $this->requestedRelationshipsCache->each(fn (JsonApiResource|JsonApiResourceCollection $relation) => $relation->flush());
        }

        $this->requestedRelationshipsCache = null;
    }

    /**
     * @internal
     * @infection-ignore-all
     *
     * @param  (callable(): string)  $callback
     * @return string
     */
    private function rememberId(callable $callback)
    {
        return $this->idCache ??= $callback();
    }

    /**
     * @internal
     * @infection-ignore-all
     *
     * @param  (callable(): string)  $callback
     * @return string
     */
    private function rememberType(callable $callback)
    {
        return $this->typeCache ??= $callback();
    }

    /**
     * @internal
     * @infection-ignore-all
     *
     * @param  (callable(): Collection<string, JsonApiResource|JsonApiResourceCollection>)  $callback
     * @return Collection<string, JsonApiResource|JsonApiResourceCollection>
     */
    private function rememberRequestRelationships(callable $callback)
    {
        return $this->requestedRelationshipsCache ??= $callback();
    }

    /**
     * @internal
     *
     * @return Collection<string, JsonApiResource|JsonApiResourceCollection>|null
     */
    public function requestedRelationshipsCache()
    {
        return $this->requestedRelationshipsCache;
    }
}
