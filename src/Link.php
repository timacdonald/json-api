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

    public function __construct(string $href, array $meta = [])
    {
        $this->href = $href;

        $this->meta = $meta;
    }

    public function jsonSerialize(): array
    {
        return [
            'href' => $this->href,
            'meta' => (object) $this->meta,
        ];
    }
}
