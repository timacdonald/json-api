<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TiMacDonald\JsonApi\Exceptions\ResourceIdentificationException;
use TiMacDonald\JsonApi\ResourceIdentifier;

trait Identification
{
    /**
     * @internal
     *
     * @var  (callable(mixed): string)|null
     */
    private static $idResolver;

    /**
     * @internal
     *
     * @var  (callable(mixed): string)|null
     */
    private static $typeResolver;

    /**
     * @internal
     *
     * @var array<int, (callable(ResourceIdentifier): void)>
     */
    private array $resourceIdentifierCallbacks = [];

    /**
     * @api
     *
     * @param (callable(ResourceIdentifier): void) $callback
     * @return $this
     */
    public function withResourceIdentifier(callable $callback)
    {
        $this->resourceIdentifierCallbacks[] = $callback;

        return $this;
    }

    /**
     * @api
     *
     * @param (callable(mixed): string) $resolver
     * @return void
     */
    public static function resolveIdUsing(callable $resolver)
    {
        self::$idResolver = $resolver;
    }

    /**
     * @api
     *
     * @param (callable(mixed): string) $resolver
     * @return void
     */
    public static function resolveTypeUsing(callable $resolver)
    {
        self::$typeResolver = $resolver;
    }

    /**
     * @internal
     *
     * @return void
     */
    public static function resolveIdNormally()
    {
        self::$idResolver = null;
    }

    /**
     * @internal
     *
     * @return void
     */
    public static function resolveTypeNormally()
    {
        self::$typeResolver = null;
    }

    /**
     * @internal
     *
     * @return string
     */
    public function toUniqueResourceIdentifier(Request $request)
    {
        return "type:{$this->resolveType($request)};id:{$this->resolveId($request)};";
    }

    /**
     * @internal
     *
     * @return string
     */
    private function resolveId(Request $request)
    {
        return $this->rememberId(fn (): string => $this->toId($request));
    }

    /**
     * @internal
     *
     * @return string
     */
    private function resolveType(Request $request)
    {
        return $this->rememberType(fn (): string => $this->toType($request));
    }

    /**
     * @internal
     *
     * @return (callable(mixed): string)
     */
    private static function idResolver()
    {
        return self::$idResolver ??= static function (mixed $resource): string {
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
     *
     * @return (callable(mixed): string)
     */
    private static function typeResolver()
    {
        return self::$typeResolver ??= static function (mixed $resource): string {
            if (! $resource instanceof Model) {
                throw ResourceIdentificationException::attemptingToDetermineTypeFor($resource);
            }

            return Str::camel($resource->getTable());
        };
    }

    /**
     * @internal
     *
     * @return ResourceIdentifier
     */
    public function resolveResourceIdentifier(Request $request)
    {
        return tap($this->toResourceIdentifier($request), function (ResourceIdentifier $identifier): void {
            foreach ($this->resourceIdentifierCallbacks as $callback) {
                $callback($identifier);
            }
        });
    }
}
