<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\PotentiallyMissing;
use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\Contracts\Flushable;

/**
 * @internal
 */
final class UnknownRelationship implements PotentiallyMissing, Flushable
{
    /**
     * @var mixed
     */
    private $resource;

    /**
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return mixed
     */
    public function toResourceLink(Request $request)
    {
        return $this->resource;
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
        //
    }

    public function isMissing(): bool
    {
        return $this->resource instanceof PotentiallyMissing && $this->resource->isMissing();
    }
}
