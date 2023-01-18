<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use ReturnTypeWillChange;
use stdClass;

final class RelationshipObject implements JsonSerializable
{
    use Concerns\Links;
    use Concerns\Meta;

    /**
     * @internal
     *
     * @var ResourceIdentifier|null|array<int, ResourceIdentifier>
     */
    private ResourceIdentifier|array|null $data;

    /**
     * @api
     *
     * @param array<int, Link> $links
     * @param array<string, mixed> $meta
     * @return self
     */
    public static function toOne(ResourceIdentifier|null $data, array $links = [], array $meta = [])
    {
        return new self($data, $links, $meta);
    }

    /**
     * @api
     *
     * @param array<int, ResourceIdentifier> $data
     * @param array<int, Link> $links
     * @param array<string, mixed> $meta
     * @return self
     */
    public static function toMany(array $data, array $links = [], array $meta = [])
    {
        return new self($data, $links, $meta);
    }

    /**
     * @internal
     *
     * @param ResourceIdentifier|null|array<int, ResourceIdentifier> $data
     * @param array<int, Link> $links
     * @param array<string, mixed> $meta
     */
    private function __construct(ResourceIdentifier|array|null $data, array $links = [], array $meta = [])
    {
        $this->data = $data;

        $this->links = $links;

        $this->meta = $meta;
    }


    /**
     * @internal
     *
     * @return array{data: ResourceIdentifier|null|array<int, ResourceIdentifier>, meta: stdClass, links: stdClass}
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'data' => $this->data,
            'meta' => (object) $this->meta,
            'links' => (object) self::parseLinks($this->links),
        ];
    }
}
