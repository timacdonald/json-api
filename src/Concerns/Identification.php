<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Closure;
use Illuminate\Http\Request;

/**
 * @internal
 */
trait Identification
{
    /**
     * @internal
     */
    private static ?Closure $idResolver;

    /**
     * @internal
     */
    private static ?Closure $typeResolver;

    /**
     * @internal
     */
    public static function resolveIdNormally(): void
    {
        self::$idResolver = null;
    }

    /**
     * @internal
     */
    public static function resolveTypeNormally(): void
    {
        self::$typeResolver = null;
    }

    /**
     * @internal
     */
    public function toResourceIdentifier(Request $request): array
    {
        return [
            'data' => [
                'id' => $this->toId($request),
                'type' => $this->toType($request),
            ],
        ];
    }

    /**
     * @internal
     */
    public function toUniqueResourceIdentifier(Request $request): string
    {
        return "type:{$this->toType($request)} id:{$this->toId($request)}";
    }
}
