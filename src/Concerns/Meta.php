<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

trait Meta
{
    /**
     * @internal
     *
     * @var array<string, mixed>
     */
    private array $meta = [];

    /**
     * @api
     *
     * @param  array<string, mixed>  $meta
     * @return $this
     */
    public function withMeta(array $meta)
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }
}
