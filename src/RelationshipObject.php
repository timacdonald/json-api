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
     * @var ResourceIdentifier|null|array<ResourceIdentifier>
     */
    private $data;

    /**
     * @param ResourceIdentifier|null $data
     * @param array<int, Link> $links
     * @param array<string, mixed> $meta
     * @return self
     */
    public static function toOne($data, $links = [], $meta = [])
    {
        return new self($data, $links, $meta);
    }

    /**
     * @param array<ResourceIdentifier> $data
     * @param array<int, Link> $links
     * @param array<string, mixed> $meta
     * @return self
     */
    public static function toMany($data, $links = [], $meta = [])
    {
        return new self($data, $links, $meta);
    }

    /**
     * @param ResourceIdentifier|null|array<ResourceIdentifier> $data
     * @param array<int, Link> $links
     * @param array<string, mixed> $meta
     */
    private function __construct($data, $links = [], $meta = [])
    {
        $this->data = $data;

        $this->links = $links;

        $this->meta = $meta;
    }


    /**
     * @return array{data: ResourceIdentifier|null|array<ResourceIdentifier>, meta: stdClass, links: stdClass}
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
