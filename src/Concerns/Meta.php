<?php

namespace TiMacDonald\JsonApi\Concerns;

trait Meta
{
    /**
     * @var array<string, mixed> $meta
     */
    private array $meta = [];

    /**
     * @param array<string, mixed> $meta
     */
    public function withMeta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }
}
