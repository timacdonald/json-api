<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;

final class RelationshipLink implements JsonSerializable
{
    use Concerns\Links;
    use Concerns\Meta;

    private ?ResourceIdentifier $data;

    /**
     * @param array<string|int, string|Link> $links
     * @param array<string, mixed> $meta
     */
    public function __construct(?ResourceIdentifier $data, array $links = [], array $meta = [])
    {
        $this->data = $data;

        $this->links = $links;

        $this->meta = $meta;
    }

    /**
     * @return array{data: ?ResourceIdentifier, meta: stdClass, links: stdClass}
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'meta' => (object) $this->meta,
            'links' => (object) self::parseLinks($this->links),
        ];
    }
}
