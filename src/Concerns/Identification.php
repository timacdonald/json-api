<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TiMacDonald\JsonApi\Exceptions\ResourceIdentificationException;

trait Identification
{
    /**
     * @internal
     *
     * @var ?callable
     */
    private static $idResolver = null;

    /**
     * @internal
     *
     * @var ?callable
     */
    private static $typeResolver = null;

    /**
     * @api
     *
     * @param callable $resolver
     * @return void
     */
    public static function resolveIdUsing($resolver)
    {
        self::$idResolver = $resolver;
    }

    /**
     * @api
     *
     * @param callable $resolver
     * @return void
     */
    public static function resolveTypeUsing($resolver)
    {
        self::$typeResolver = $resolver;
    }

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
    public function toUniqueResourceIdentifier(Request $request): string
    {
        return "type:{$this->resolveType($request)};id:{$this->resolveId($request)};";
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
     */
    private static function idResolver(): Closure
    {
        return self::$idResolver ??= function ($resource): string {
            if (! $resource instanceof Model) {
                throw ResourceIdentificationException::attemptingToDetermineIdFor($resource);
            }

            /**
             * @phpstan-ignore-next-line
             */
            return (string) $resource->getKey();
        };
    }

    /**
     * @internal
     */
    private static function typeResolver(): Closure
    {
        return self::$typeResolver ??= function ($resource): string {
            if (! $resource instanceof Model) {
                throw ResourceIdentificationException::attemptingToDetermineTypeFor($resource);
            }

            return Str::camel($resource->getTable());
        };
    }
}
