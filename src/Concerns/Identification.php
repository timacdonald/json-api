<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use TiMacDonald\JsonApi\Exceptions\ResourceIdentificationException;
use TiMacDonald\JsonApi\ResourceIdentifier;

trait Identification
{
    private const ID_RESOLVER_KEY = self::class.':$idResolver';

    private const TYPE_RESOLVER_KEY = self::class.':$typeResolver';

    private string|null $idCache = null;

    private string|null $typeCache = null;

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
     * @param (callable(mixed): string) $callback
     * @return void
     */
    public static function resolveIdUsing(callable $callback)
    {
        App::instance(self::ID_RESOLVER_KEY, $callback);
    }

    /**
     * @api
     *
     * @param (callable(mixed): string) $callback
     * @return void
     */
    public static function resolveTypeUsing(callable $callback)
    {
        App::instance(self::TYPE_RESOLVER_KEY, $callback);
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
        return $this->idCache ??= $this->toId($request);
    }

    /**
     * @internal
     *
     * @return string
     */
    private function resolveType(Request $request)
    {
        return $this->typeCache ??= $this->toType($request);
    }

    /**
     * @internal
     *
     * @return (callable(mixed, Request): string)
     */
    private static function idResolver()
    {
        if (! App::bound(self::ID_RESOLVER_KEY)) {
            App::instance(self::ID_RESOLVER_KEY, function (mixed $resource, Request $request): string {
                if (! $resource instanceof Model) {
                    throw ResourceIdentificationException::attemptingToDetermineIdFor($resource);
                }

                /**
                 * @phpstan-ignore-next-line
                 */
                return (string) $resource->getKey();
            });
        }

        return App::make(self::ID_RESOLVER_KEY);
    }

    /**
     * @internal
     *
     * @return (callable(mixed, Request): string)
     */
    private static function typeResolver()
    {
        if (! App::bound(self::TYPE_RESOLVER_KEY)) {
            App::instance(self::TYPE_RESOLVER_KEY, function (mixed $resource, Request $request): string {
                if (! $resource instanceof Model) {
                    throw ResourceIdentificationException::attemptingToDetermineTypeFor($resource);
                }

                return Str::camel($resource->getTable());
            });
        }

        return App::make(self::TYPE_RESOLVER_KEY);
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
