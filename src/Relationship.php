<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;

/**
 * @see https://jsonapi.org/format/#document-resource-object-relationships
 */
class Relationship implements JsonSerializable
{
    private ?ResourceIdentifier $data;

    private array $links;

    private array $meta;

    public function __construct(?ResourceIdentifier $data = null, array $links = [], array $meta = [])
    {
        $this->data = $data;

        $this->links = $links;

        $this->meta = $meta;
    }

    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data ?? new stdClass(),
            'meta' => (object) $this->meta,
            'links' => (object) $this->links,
        ];
    }
}
