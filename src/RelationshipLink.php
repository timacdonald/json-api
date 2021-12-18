<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;

final class RelationshipLink implements JsonSerializable
{
    private ResourceIdentifier $data;

    private array $links;

    private array $meta;

    public function __construct(ResourceIdentifier $data, array $links = [], array $meta = [])
    {
        $this->data = $data;

        $this->links = $links;

        $this->meta = $meta;
    }

    /**
     * @internal
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'meta' => (object) $this->meta,
            'links' => (object) $this->links,
        ];
    }
}
