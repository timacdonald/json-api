<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\Link;

use function is_string;

trait Links
{
    /**
     * @internal
     *
     * @var array<int, Link>
     */
    private array $links = [];

    /**
     * @api
     *
     * @param  array<int, Link>  $links
     * @return $this
     */
    public function withLinks(array $links)
    {
        $this->links = array_merge(
            $this->links,
            Collection::make($links)
                ->map(fn ($value, $key) => is_string($key) ? new Link($key, $value) : $value)
                ->values()
                ->all()
        );

        return $this;
    }

    /**
     * @internal
     *
     * @param  array<int, Link>  $links
     * @return array<string, Link>
     */
    private static function parseLinks(array $links)
    {
        return Collection::make($links)
            ->mapWithKeys(fn (Link $link): array => [
                $link->key => $link,
            ])
            ->all();
    }
}
