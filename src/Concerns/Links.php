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
     */
    private function resolveLinks(Request $request): array
    {
        return Collection::make($this->toLinks($request))
            ->mapWithKeys(
                /**
                 * @param mixed $value
                 * @param int|string $key
                 */
                fn ($value, $key): array => $value instanceof Link
                    ? [$value->key() => $value]
                    : [$key => $value]
            )
            ->all();
    }
}
