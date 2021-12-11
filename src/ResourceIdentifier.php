<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;

/**
 * @see https://jsonapi.org/format/#document-resource-identifier-objects
 */
class ResourceIdentifier implements JsonSerializable
{
    private string $id;

    private string $type;

    private array $meta;

    public function __construct(string $id, string $type, array $meta = [])
    {
        $this->id = $id;

        $this->type = $type;

        $this->meta = $meta;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'meta' => (object) $this->meta,
        ];
    }
}
