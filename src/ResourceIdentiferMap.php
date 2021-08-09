<?php

namespace TiMacDonald\JsonApi;

use Closure;
use Exception;
use Illuminate\Support\Arr;
use RuntimeException;
use function array_key_exists;

class ResourceIdentiferMap
{
    /**
     * @var array<class-string, array{0: string, 1: \Closure(mixed $resource): string|null>
     */
    private array $map = [];

    /**
     * @param array<class-string, string|array{0: string, 1: \Closure(mixed $resource): string}> $map
     * @param \Closure(mixed $resource): string|null $default
     */
    public function __construct(array $map, ?Closure $default = null)
    {
        foreach ($map as $class => $tuple) {
            [$type, $idResolver] = is_array($tuple)
                ? $tuple
                : [$tuple, $default];

            $this->ensureTypeIsUnique($type);

            $this->map[$class] = [$type, $idResolver];
        }
    }


    /**
     * @param array<class-string, string|array{0: string, 1: \Closure(mixed $resource): string}> $map
     * @param \Closure(mixed $resource): string|null $default
     */
    public function add(array $map, ?Closure $default = null): self
    {
        foreach ($map as $class => $tuple) {
            $this->ensureClassIsUnique($class);
        }

        return new self(array_merge($this->map, $map), $default);
    }

    public function resolveType(object $resource): string
    {
        return $this->find($resource)[0];
    }

    public function resolveId(object $resource): string
    {
        $resolver = $this->find($resource)[1] ?? self::missingIdResolver($resource);

        return $resolver($resource);
    }

    /**
     * @return array{0: string, 1: \Closure(object $resource): string}
     */
    private function find(object $resource): array
    {
        if (! array_key_exists($resource::class, $this->map)) {
            throw new RuntimeException('Unable to find resource details for class '.$resource::class.'. Did you forget to register it in the service provider?');
        }

        return $this->map[$resource::class];
    }

    private function ensureTypeIsUnique(string $type): void
    {
        $types = Arr::pluck($this->map, '0');

        if (array_search($type, $types, true) !== false) {
            throw new RuntimeException("Unable to register type '{$type}' as it has already been registered.");
        }
    }

    /**
     * @param class-string $class
     */
    private function ensureClassIsUnique(string $class): void
    {
        if (array_key_exists($class, $this->map)) {
            throw new RuntimeException("Unable to register class '{$class}' as it has already been registered.");
        }
    }

    private static function missingIdResolver(object $resource): Closure
    {
        return function () use ($resource): void {
            throw new RuntimeException('No id resolver provided for class '.$resource::class.'.');
        };
    }
}
