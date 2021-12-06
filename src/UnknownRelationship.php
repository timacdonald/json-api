<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Illuminate\Support\Collection;

/**
 * @internal
 */
class UnknownRelationship
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
    public function toResourceIdentifier()
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
