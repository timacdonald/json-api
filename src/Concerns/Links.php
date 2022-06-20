<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\Link;

/**
 * @internal
 */
trait Links
{
    /**
     * @var array<string|int, string|Link>
     */
    private array $links = [];

    /**
     * @param array<string|int, string|Link> $links
     */
    public function withLinks(array $links): static
    {
        $this->links = array_merge($this->links, $links);

        return $this;
    }

    /**
     * @param array<string|int, string|Link> $links
     * @return array<string, Link>
     */
    private function parseLinks(array $links): array
    {
        return Collection::make($links)
            ->mapWithKeys(
                /**
                 * @param string|Link $value
                 * @param string|int $key
                 */
                fn ($value, $key): array => $value instanceof Link
                    ? [$value->key() => $value]
                    : [$key => new Link($value)]
            )
            ->all();
    }
}
