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
     * @var callable|null
     */
    private static $idResolver = null;

    /**
     * @internal
     *
     * @var callable|null
     */
    private static $typeResolver = null;

    /**
     * @internal
     *
     * @var array<callable(ResourceIdentifier): void>
     */
    private $resourceIdentifierCallbacks = [];

    /**
     * @api
     *
     * @param callable(ResourceIdentifier): void $callback
     * @return $this
     */
    public function withResourceIdentifier($callback)
    {
        $this->resourceIdentifierCallbacks[] = $callback;

        return $this;
    }

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
     * @param Request $request
     * @return string
     */
    public function toUniqueResourceIdentifier($request)
    {
        return "type:{$this->resolveType($request)};id:{$this->resolveId($request)};";
    }

    /**
     * @internal
     *
     * @param Request $request
     * @return string
     */
    private function resolveId($request)
    {
        return $this->rememberId(fn (): string => $this->toId($request));
    }

    /**
     * @internal
     *
     * @param Request $request
     * @return string
     */
    private function resolveType($request)
    {
        return $this->rememberType(fn (): string => $this->toType($request));
    }

    /**
     * @internal
     *
     * @return callable
     */
    private static function idResolver()
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
     *
     * @return callable
     */
    private static function typeResolver()
    {
        return self::$typeResolver ??= function ($resource): string {
            if (! $resource instanceof Model) {
                throw ResourceIdentificationException::attemptingToDetermineTypeFor($resource);
            }

            return Str::camel($resource->getTable());
        };
    }

    /**
     * @internal
     *
     * @param Request $request
     * @return ResourceIdentifier
     */
    public function resolveResourceIdentifier($request)
    {
        return tap($this->toResourceIdentifier($request), function (ResourceIdentifier $identifier): void {
            foreach ($this->resourceIdentifierCallbacks as $callback) {
                $callback($identifier);
            }
        });
    }
}
