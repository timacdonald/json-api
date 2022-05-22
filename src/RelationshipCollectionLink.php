<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;

final class RelationshipCollectionLink implements JsonSerializable
{
    /**
     * @var array<ResourceIdentifier>
     */
    private array $data;

    /**
     * @var array<string|int, string|Link>
     */
    private array $links;

    /**
     * @var array<string, mixed>
     */
    private array $meta;

    /**
     * @param array<string|int, string|Link> $links
     * @param array<string, mixed> $meta
     */
    public function __construct(array $data, array $links = [], array $meta = [])
    {
        $this->data = $data;

        $this->links = $links;

        $this->meta = $meta;
    }

    /**
     * @return array{data: ResourceIdentifier, meta: stdClass, links: stdClass}
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
