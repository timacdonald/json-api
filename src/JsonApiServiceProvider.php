<?php

namespace TiMacDonald\JsonApi;

use Closure;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;
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
            return function (Model $resource): string {
                return $resource->getKey();
            };
        });

        $this->app->singleton(ResourceTypeResolver::class, function (): Closure {
            return function (Model $resource): string {
                return Str::of($resource::class)->classBasename()->camel();
            };
        });
    }
}
