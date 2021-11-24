<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Illuminate\Support\Collection;

/**
 * @internal
 */
class UnknownRelationship
{
    public function __construct(private mixed $resource)
    {
        //
    }

    public function toResourceIdentifier(): mixed
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
}
