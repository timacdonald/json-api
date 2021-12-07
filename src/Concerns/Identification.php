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
    private ?string $idCache = null;

    /**
     * @internal
     */
    private ?string $typeCache = null;

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
                'id' => $this->resolveId($request),
                'type' => $this->resolveType($request),
            ],
        ];
    }

    /**
     * @internal
     */
    public function toUniqueResourceIdentifier(Request $request): string
    {
        return "type:{$this->resolveType($request)} id:{$this->resolveId($request)}";
    }

    /**
     * @internal
     */
    private function resolveId(Request $request): string
    {
        return $this->rememberId(fn (): string => $this->toId($request));
    }

    /**
     * @internal
     */
    private function resolveType(Request $request): string
    {
        return $this->rememberType(fn (): string => $this->toType($request));
    }

    /**
     * @internal
     * @infection-ignore-all
     */
    private function rememberType(Closure $closure): string
    {
        return $this->typeCache ??= $closure();
    }

    /**
     * @internal
     * @infection-ignore-all
     */
    private function rememberId(Closure $closure): string
    {
        return $this->idCache ??= $closure();
    }
}
