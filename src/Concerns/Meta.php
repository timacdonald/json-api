<?php

namespace TiMacDonald\JsonApi\Concerns;

trait Meta
{
    /**
     * @var array<string, mixed> $meta
     */
    private array $meta = [];

    public function withMeta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }
}
