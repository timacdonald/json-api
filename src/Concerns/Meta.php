<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

trait Meta
{
    /**
     * @var array<string, mixed> $meta
     */
    private array $meta = [];

    /**
     * @api
     *
     * @param array<string, mixed> $meta
     * @return $this
     */
    public function withMeta($meta)
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }
}
