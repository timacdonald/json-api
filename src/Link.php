<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;

/**
 * @see https://jsonapi.org/format/#document-resource-object-links
 */
final class Link implements JsonSerializable
{
    private string $href;

    /**
     * @var array<string, mixed>
     */
    private array $meta;

    private string $key = 'unknown';

    /**
     * @param array<string, mixed> $meta
     */
    public static function self(string $href, array $meta = []): self
    {
        return tap(new self($href, $meta), fn (self $instance): string => $instance->key = 'self');
    }

    /**
     * @param array<string, mixed> $meta
     */
    public static function related(string $href, array $meta = []): self
    {
        return tap(new self($href, $meta), fn (self $instance): string => $instance->key = 'related');
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

    public function withMeta(array $meta): self
    {
        $this->meta = array_merge_recursive($this->meta, $meta);

        return $this;
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
