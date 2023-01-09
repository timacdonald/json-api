<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use TiMacDonald\JsonApi\Support\Fields;
use TiMacDonald\JsonApi\Support\Includes;

trait Caching
{
    /**
     * @internal
     *
     * @var string|null
     */
    private $idCache = null;

    /**
     * @internal
     *
     * @var string|null
     */
    private $typeCache = null;

    /**
     * @internal
     *
     * @var Collection<string, JsonApiResource|JsonApiResourceCollection>|null
     */
    private $requestedRelationshipsCache = null;

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
            $this->requestedRelationshipsCache->each(
                fn (JsonApiResource|JsonApiResourceCollection $relation) => $relation->flush()
            );
        }

        $this->requestedRelationshipsCache = null;
    }

    /**
     * @internal
     * @infection-ignore-all
     *
     * @param callable $closure
     * @return string
     */
    private function rememberId($closure)
    {
        return $this->idCache ??= $closure();
    }

    /**
     * @internal
     * @infection-ignore-all
     *
     * @param callable $closure
     * @return string
     */
    private function rememberType($closure)
    {
        return $this->typeCache ??= $closure();
    }

    /**
     * @internal
     * @infection-ignore-all
     *
     * @param callable $closure
     * @return Collection<string, JsonApiResource|JsonApiResourceCollection>
     */
    private function rememberRequestRelationships($closure)
    {
        return $this->requestedRelationshipsCache ??= $closure();
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
