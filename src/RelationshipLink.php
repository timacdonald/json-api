<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;
use TiMacDonald\JsonApi\Concerns\Links;

final class RelationshipLink implements JsonSerializable
{
    use Links;

    private ?ResourceIdentifier $data;

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
    public function __construct(?ResourceIdentifier $data, array $links = [], array $meta = [])
    {
        $this->data = $data;

        $this->links = $links;

        $this->meta = $meta;
    }

    public function withLinks(array $links): self
    {
        $this->links = array_merge_recursive($this->links, $links);

        return $this;
    }

    public function withMeta(array $meta): self
    {
        $this->meta = array_merge_recursive($this->meta, $meta);

        return $this;
    }

    /**
     * @return array{data: ?ResourceIdentifier, meta: stdClass, links: stdClass}
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'meta' => (object) $this->meta,
            'links' => (object) $this->parseLinks($this->links),
        ];
    }
}
