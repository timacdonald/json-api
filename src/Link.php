<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;

/**
 * @see https://jsonapi.org/format/#document-resource-object-links
 */
class Link implements JsonSerializable
{
    private string $href;

    private array $meta;

    private string $key = 'unknown';

    public static function self(string $href, array $meta = []): self
    {
        return tap(new self($href, $meta), fn (self $instance) => $instance->key = 'self');
    }

    public static function related(string $href, array $meta = []): self
    {
        return tap(new self($href, $meta), fn (self $instance) => $instance->key = 'related');
    }

    private function __construct(string $href, array $meta = [])
    {
        $this->href = $href;

        $this->meta = $meta;
    }

    /**
     * @internal
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
        return [
            'self' => 'self',
            'related' => 'related',
        ][$this->key];
    }
}
