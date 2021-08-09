<?php

namespace TiMacDonald\JsonApi;

use Closure;
use Illuminate\Contracts\Support\DeferrableProvider;
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
            return function (mixed $resource): string {
                if (! is_object($resource)) {
                    throw new \Exception('Unable to automatically detect the resource id for type '.gettype($resource).'. You should register your own custom ResourceIdResolver in the container or override the resourceId() method on the JsonResource.');
                }

                if (! method_exists($resource, 'getKey')) {
                    throw new \Exception('Unable to automatically detect the resource id for class '.$resource::class.'. You should register your own custom ResourceIdResolver in the container or override the resourceId() method on the JsonResource.');
                }

                return $resource->getKey();
            };
        });

        $this->app->singleton(ResourceTypeResolver::class, function (): Closure {
            return function (mixed $resource): string {
                if (! is_object($resource)) {
                    throw new \Exception('Unable to automatically detect the resource type for type '.gettype($resource).'. You should register your own custom ResourceTypeResolver in the container or override the resourceType() method on the JsonResource.');
                }

                return Str::of($resource::class)->classBasename()->camel();
            };
        });
    }
}
