<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\PotentiallyMissing;
use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\Contracts\Flushable;
use TiMacDonald\JsonApi\RelationshipLink;

/**
 * @internal
 */
final class NullRelationship implements Flushable
{
    /**
     * @return mixed
     */
    public function toResourceLink(Request $request)
    {
        return new RelationshipLink(null);
    }

    public function included(): Collection
    {
        return new Collection([]);
    }

    public function includable(): self
    {
        return $this;
    }

    public function shouldBePresentInIncludes(): bool
    {
        return false;
    }

    public function flush(): void
    {
        // noop
    }
}
