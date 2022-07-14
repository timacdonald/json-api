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
     * @var array<string|int, string|Link>
     */
    private array $links = [];

    /**
     * @api
     *
     * @param array<int, Link>|array<string, string> $links
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
     * @param array<int, Link>|array<string, string> $links
     * @return array<string, Link>
     */
    private function parseLinks(array $links): array
    {
        return Collection::make($links)
            ->mapWithKeys(
                fn (Link|string $value, int|string $key) => $value instanceof Link
                ? [$value->key() => $value]
                : [$key => new Link($value)]
            )
            ->all();
    }
}
