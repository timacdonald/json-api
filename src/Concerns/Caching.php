<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;

/**
 * @internal
 *
 * @todo can we get rid of this?
 */
trait Caching
{
    /**
     * @internal
     *
     * @infection-ignore-all
     *
     * @return void
     */
    public function flush()
    {
        // TODO can we just let this garbage collect?
        $this->requestedRelationshipsCache?->each(fn (JsonApiResource|JsonApiResourceCollection $relation) => $relation->flush());

        $this->requestedRelationshipsCache = null;

        $this->idCache = null;

        $this->typeCache = null;
    }

    /**
     * @internal
     *
     * @return Collection<string, JsonApiResource|JsonApiResourceCollection>|null
     */
    public function requestedRelationshipsCache()
    {
        // TODO can we remove this if we ditch caching? Only here for tests.
        return $this->requestedRelationshipsCache;
    }
}
