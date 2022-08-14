<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;

/**
 * @see https://jsonapi.org/format/#document-links
 */
final class Link implements JsonSerializable
{
    use Concerns\Meta;

    /**
     * @internal
     */
    public readonly string $type;

    private string $href;

    /**
     * @param array<string, mixed> $meta
     */
    public static function self(string $href, array $meta = []): self
    {
        return new self('self', $href, $meta);
    }

    /**
     * @param array<string, mixed> $meta
     */
    public static function related(string $href, array $meta = []): self
    {
        return new self('related', $href, $meta);
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(string $type, string $href, array $meta = [])
    {
        $this->type = $type;

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
}
