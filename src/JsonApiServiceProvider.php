<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Closure;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;
use RuntimeException;
use TiMacDonald\JsonApi\Contracts\ResourceIdResolver;
use TiMacDonald\JsonApi\Contracts\ResourceTypeResolver;

class JsonApiServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @return array<class-string>
     */
    public function provides(): array
    {
        return [
            ResourceIdResolver::class,
            ResourceTypeResolver::class,
        ];
    }

    public function register(): void
    {
        $this->app->singleton(ResourceIdResolver::class, function (): Closure {
            return function ($resourceObject): string {
                if ($resourceObject instanceof Model) {
                    return (string) $resourceObject->getKey();
                }

                if (is_object($resourceObject)) {
                    throw new RuntimeException('Unable to resolve Resource Object id for class '.$resourceObject::class.'.');
                }

                throw new RuntimeException('Unable to resolve Resource Object id for type '.gettype($resourceObject).'.');
            };
        });

        $this->app->singleton(ResourceTypeResolver::class, function (): Closure {
            return function ($resourceObject): string {
                if ($resourceObject instanceof Model) {
                    return Str::camel($resourceObject->getTable());
                }

                if (! is_object($resourceObject)) {
                    throw new RuntimeException('Unable to resolve Resource Object type for type '.gettype($resourceObject).'.');
                }

                throw new RuntimeException('Unable to resolve Resource Object type for class '.$resourceObject::class.'.');
            };
        });
    }
}
