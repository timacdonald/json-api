<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use TiMacDonald\JsonApi\Exceptions\ResourceIdentificationException;
use TiMacDonald\JsonApi\ResourceIdentifier;

/**
 * @internal
 */
trait Identification
{
    private const ID_RESOLVER_KEY = self::class.':$idResolver';

    private const TYPE_RESOLVER_KEY = self::class.':$typeResolver';

    private string|null $idCache = null;

    private string|null $typeCache = null;

    /**
     * @var array<int, (callable(ResourceIdentifier): void)>
     */
    private array $resourceIdentifierCallbacks = [];

    /**
     * @param (callable(mixed): string) $callback
     * @return void
     */
    public static function resolveIdUsing(callable $callback)
    {
        App::instance(self::ID_RESOLVER_KEY, $callback);
    }

    /**
     * @param (callable(mixed): string) $callback
     * @return void
     */
    public static function resolveTypeUsing(callable $callback)
    {
        App::instance(self::TYPE_RESOLVER_KEY, $callback);
    }

    /**
     * @return (callable(mixed, Request): string)
     */
    private static function idResolver()
    {
        if (! App::bound(self::ID_RESOLVER_KEY)) {
            return App::instance(self::ID_RESOLVER_KEY, function (mixed $resource, Request $request): string {
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
     * @return (callable(mixed, Request): string)
     */
    private static function typeResolver()
    {
        if (! App::bound(self::TYPE_RESOLVER_KEY)) {
            return App::instance(self::TYPE_RESOLVER_KEY, function (mixed $resource, Request $request): string {
                if (! $resource instanceof Model) {
                    throw ResourceIdentificationException::attemptingToDetermineTypeFor($resource);
                }

                return Str::camel($resource->getTable());
            });
        }

        return App::make(self::TYPE_RESOLVER_KEY);
    }

    /**
     * @param (callable(ResourceIdentifier): void) $callback
     * @return $this
     */
    public function pipeResourceIdentifier(callable $callback)
    {
        $this->resourceIdentifierCallbacks[] = $callback;

        return $this;
    }

    /**
     * @internal
     *
     * @return ResourceIdentifier
     */
    public function resolveResourceIdentifier(Request $request)
    {
        return with($this->toResourceIdentifier($request), function (ResourceIdentifier $identifier): ResourceIdentifier {
            foreach ($this->resourceIdentifierCallbacks as $callback) {
                $identifier = $callback($identifier);
            }

            return $identifier;
        });
    }

    /**
     * @internal
     *
     * @return array{0: string, 1: string}
     */
    public function uniqueKey(Request $request)
    {
        return [$this->resolveType($request), $this->resolveId($request)];
    }

    /**
     * @return string
     */
    private function resolveId(Request $request)
    {
        return $this->idCache ??= $this->toId($request);
    }

    /**
     * @return string
     */
    private function resolveType(Request $request)
    {
        return $this->typeCache ??= $this->toType($request);
    }
}
