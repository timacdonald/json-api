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
     *
     * @var bool
     */
    private static $minimalAttributes = false;

    /**
     * @api
     *
     * @param ?callable $callback
     * @return void
     */
    public static function minimalAttributes($callback = null)
    {
        self::$minimalAttributes = true;

        if ($callback === null) {
            return;
        }

        try {
            $callback();
        } finally {
            self::$minimalAttributes = false;
        }
    }

    /**
     * @api
     * @infection-ignore-all
     *
     * @return void
     */
    public static function maximalAttributes()
    {
        self::$minimalAttributes = false;
    }

    /**
     * @internal
     *
     * @param Request $request
     * @return Collection<string, mixed>
     */
    private function requestedAttributes($request)
    {
        return Collection::make($this->toAttributes($request))
            ->only($this->requestedFields($request))
            ->map(fn (mixed $value): mixed => value($value))
            ->reject(fn (mixed $value): bool => $value instanceof PotentiallyMissing && $value->isMissing());
    }

    /**
     * @internal
     *
     * @param Request $request
     * @return array<string>|null
     */
    private function requestedFields($request)
    {
        return Fields::getInstance()->parse($request, $this->toType($request), self::$minimalAttributes);
    }
}
