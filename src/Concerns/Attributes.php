<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\PotentiallyMissing;
use Illuminate\Support\Collection;
use TiMacDonald\JsonApi\Support\Fields;

trait Attributes
{
    /**
     * @internal
     */
    private static bool $minimalAttributes = false;

    /**
     * @internal
     * @infection-ignore-all
     */
    public static function maximalAttributes(): void
    {
        self::$minimalAttributes = false;
    }

    /**
     * @internal
     */
    private function requestedAttributes(Request $request): Collection
    {
        return Collection::make($this->toAttributes($request))
            ->only(Fields::getInstance()->parse($request, $this->toType($request), self::$minimalAttributes))
            ->map(fn ($value) => value($value))
            ->reject(fn ($value) => $value instanceof PotentiallyMissing && $value->isMissing());
    }
}
