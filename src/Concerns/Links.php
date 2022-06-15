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
     * @internal
     * @return array<string, Link>
     */
    private function resolveLinks(Request $request): array
    {
        return $this->parseLinks($this->toLinks($request));
    }

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
