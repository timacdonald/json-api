<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;

final class Link implements JsonSerializable
{
    use Concerns\Meta;

    /**
     * @internal
     */
    public string $key;

    /**
     * @internal
     */
    private string $href;

    /**
     * @api
     *
     * @param array<string, mixed> $meta
     * @return self
     */
    public static function self(string $href, array $meta = [])
    {
        return new self('self', $href, $meta);
    }

    /**
     * @api
     *
     * @param array<string, mixed> $meta
     * @return self
     */
    public static function related(string $href, array $meta = [])
    {
        return new self('related', $href, $meta);
    }

    /**
     * @api
     *
     * @param array<string, mixed> $meta
     */
    public function __construct(string $key, string $href, array $meta = [])
    {
        $this->key = $key;

        $this->href = $href;

        $this->meta = $meta;
    }

    /**
     * @internal
     *
     * @return array{href: string, meta?: stdClass}
     */
    public function jsonSerialize(): array
    {
        return [
            'href' => $this->href,
            ...$this->meta ? ['meta' => (object) $this->meta] : [],
        ];
    }
}
