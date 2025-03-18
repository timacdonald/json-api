<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\PotentiallyMissing;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use TiMacDonald\JsonApi\Exceptions\UnknownRelationshipException;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use TiMacDonald\JsonApi\RelationshipObject;
use TiMacDonald\JsonApi\Support\Includes;
use Traversable;

use function is_array;
use function is_int;

trait Relationships
{
    /**
     * @var Collection<string, JsonApiResource|JsonApiResourceCollection>|null
     */
    private ?Collection $requestedRelationshipsCache = null;

    /**
     * @internal
     *
     * @var (callable(string, JsonApiResource): class-string)|null
     */
    private static $relationshipResourceGuesser = null;

    /**
     * @internal
     */
    private string $includePrefix = '';

    /**
     * @api
     *
     * @param  (callable(string, JsonApiResource): class-string)|null  $callback
     * @return void
     */
    public static function guessRelationshipResourceUsing(?callable $callback)
    {
        self::$relationshipResourceGuesser = $callback;
    }

    /**
     * @internal
     *
     * @return $this
     */
    public function withIncludePrefix(string $prefix)
    {
        $this->includePrefix = self::joinIncludes($this->includePrefix, $prefix);

        return $this;
    }

    /**
     * @internal
     *
     * @return Collection<int, JsonApiResource>
     */
    public function included(Request $request)
    {
        return $this->requestedRelationships($request)
            ->map(fn (JsonApiResource|JsonApiResourceCollection $include): Collection|JsonApiResource => $include->includable())
            ->merge($this->nestedIncluded($request))
            ->flatten()
            ->filter(fn (JsonApiResource $resource): bool => $resource->shouldBePresentInIncludes())
            ->values();
    }

    /**
     * @internal
     *
     * @return Collection<int, JsonApiResource>
     */
    private function nestedIncluded(Request $request)
    {
        return $this->requestedRelationships($request)
            ->flatMap(fn (JsonApiResource|JsonApiResourceCollection $resource, string $key): Collection => $resource->included($request));
    }

    /**
     * @internal
     *
     * @return Collection<string, RelationshipObject>
     */
    private function requestedRelationshipsAsIdentifiers(Request $request)
    {
        return $this->requestedRelationships($request)
            ->map(fn (JsonApiResource|JsonApiResourceCollection $resource): RelationshipObject => $resource->resolveRelationshipLink($request));
    }

    /**
     * @internal
     *
     * @return Collection<string, JsonApiResource|JsonApiResourceCollection>
     */
    private function requestedRelationships(Request $request)
    {
        return $this->requestedRelationshipsCache ??= $this->resolveRelationships($request)
            ->only($this->requestedIncludes($request))
            ->map(fn (callable $value, string $prefix): null|JsonApiResource|JsonApiResourceCollection => $this->resolveInclude($value(), $prefix))
            ->reject(fn (JsonApiResource|JsonApiResourceCollection|null $resource): bool => $resource === null);
    }

    /**
     * @internal
     *
     * @return JsonApiResource|JsonApiResourceCollection|null
     */
    private function resolveInclude(mixed $resource, string $prefix)
    {
        return match (true) {
            $resource instanceof PotentiallyMissing && $resource->isMissing() => null,
            $resource instanceof JsonApiResource || $resource instanceof JsonApiResourceCollection => $resource->withIncludePrefix(
                self::joinIncludes($this->includePrefix, $prefix)
            ),
            default => throw UnknownRelationshipException::from($resource),
        };
    }

    /**
     * @internal
     *
     * @return Collection<string, Closure(): JsonApiResource|JsonApiResourceCollection>
     */
    private function resolveRelationships(Request $request)
    {
        return Collection::make(property_exists($this, 'relationships') ? $this->relationships : [])
            ->mapWithKeys(fn (string $value, int|string $key) => ! is_int($key) ? [
                $key => $value,
            ] : [
                $value => self::guessRelationshipResource($value, $this),
            ])
            ->map(fn (string $class, string $relation): Closure => function () use ($class, $relation): JsonApiResource|JsonApiResourceCollection {
                return with($this->resource->{$relation}, function (mixed $resource) use ($class): JsonApiResource|JsonApiResourceCollection {
                    if ($resource instanceof Traversable || (is_array($resource) && ! Arr::isAssoc($resource))) {
                        return $class::collection($resource);
                    }

                    return $class::make($resource);
                });
            })->merge($this->toRelationships($request));
    }

    /**
     * @internal
     *
     * @return array<int, string>
     */
    private function requestedIncludes(Request $request)
    {
        return Includes::getInstance()->forPrefix($request, $this->includePrefix);
    }

    /**
     * @internal
     *
     * @return $this
     */
    private function includable()
    {
        return $this;
    }

    /**
     * @internal
     *
     * @return bool
     */
    private function shouldBePresentInIncludes()
    {
        return $this->resource !== null;
    }

    /**
     * @internal
     *
     * @return class-string
     */
    private static function guessRelationshipResource(string $relationship, JsonApiResource $resource)
    {
        return (self::$relationshipResourceGuesser ??= function (string $relationship, JsonApiResource $resource): string {
            $relationship = Str::of($relationship);

            foreach ([
                "App\\Http\\Resources\\{$relationship->singular()->studly()}Resource",
                "App\\Http\\Resources\\{$relationship->studly()}Resource",
            ] as $class) {
                if (class_exists($class)) {
                    return $class;
                }
            }

            throw new RuntimeException('Unable to guess the resource class for relationship ['.$value.'] for ['.$resource::class.'].');
        })($relationship, $resource);
    }

    /**
     * @internal
     */
    private static function joinIncludes(string $start, string $finish): string
    {
        $prefix = '';

        if ($start !== '') {
            $prefix = Str::finish($start, '.');
        }

        $prefix .= Str::finish($finish, '.');

        return $prefix;
    }
}
