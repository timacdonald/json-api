<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\Link;

trait Links
{
    /**
     * @internal
     *
     * @var array<int, Link>
     */
    private $links = [];

    /**
     * @api
     *
     * @param array<int, Link> $links
     * @return $this
     */
    public function withLinks($links)
    {
        $this->links = array_merge($this->links, $links);

        return $this;
    }

    /**
     * @internal
     *
     * @param array<int, Link> $links
     * @return array<string, Link>
     */
    private static function parseLinks($links)
    {
        return Collection::make($links)
            ->mapWithKeys(fn (Link $link): array => [
                $link->type => $link
            ])
            ->all();
    }
}
