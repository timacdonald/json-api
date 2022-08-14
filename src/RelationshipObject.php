<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;

/**
 * @see https://jsonapi.org/format/#document-resource-object-relationships
 */
final class RelationshipObject implements JsonSerializable
{
    use Concerns\Links;
    use Concerns\Meta;

    /**
     * @var ResourceIdentifier|null|array<ResourceIdentifier>
     */
    private ResourceIdentifier|null|array $data;

    /**
     * @param array<string|int, string|Link> $links
     * @param array<string, mixed> $meta
     */
    public static function toOne(ResourceIdentifier|null $data, array $links = [], array $meta = []): self
    {
        return new self($data, $links, $meta);
    }

    /**
     * @param array<ResourceIdentifier> $data
     * @param array<string|int, string|Link> $links
     * @param array<string, mixed> $meta
     */
    public static function toMany(array $data, array $links = [], array $meta = []): self
    {
        return new self($data, $links, $meta);
    }

    /**
     * @param ResourceIdentifier|null|array<ResourceIdentifier> $data
     * @param array<string|int, string|Link> $links
     * @param array<string, mixed> $meta
     */
    private function __construct(ResourceIdentifier|null|array $data, array $links = [], array $meta = [])
    {
        $this->data = $data;

        $this->links = $links;

        $this->meta = $meta;
    }


    /**
     * @return array{data: ResourceIdentifier|null|array<ResourceIdentifier>, meta: stdClass, links: stdClass}
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
