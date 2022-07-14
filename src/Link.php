<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;
use TiMacDonald\JsonApi\Concerns\Meta;

/**
 * @see https://jsonapi.org/format/#document-resource-object-links
 */
final class Link implements JsonSerializable
{
    use Meta;

    private string $href;

    private string $key = 'unknown';

    /**
     * @param array<string, mixed> $meta
     */
    public static function self(string $href, array $meta = []): self
    {
        return tap(new self($href, $meta), static fn (self $instance): string => $instance->key = 'self');
    }

    /**
     * @param array<string, mixed> $meta
     */
    public static function related(string $href, array $meta = []): self
    {
        return tap(new self($href, $meta), static fn (self $instance): string => $instance->key = 'related');
    }

    /**
     * @internal
     * @param array<string, mixed> $meta
     */
    public function __construct(string $href, array $meta = [])
    {
        $this->href = $href;

        $this->meta = $meta;
    }

    /**
     * @return array{href: string, meta: stdClass}
     */
    public function jsonSerialize(): array
    {
        return [
            'href' => $this->href,
            'meta' => (object) $this->meta,
        ];
    }

    /**
     * @internal
     */
    public function key(): string
    {
        return $this->key;
    }
}
