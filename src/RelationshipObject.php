<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;

final class RelationshipObject implements JsonSerializable
{
    use Concerns\Links;
    use Concerns\Meta;

    /**
     * @var ResourceIdentifier|null|array<int, ResourceIdentifier>
     */
    private ResourceIdentifier|array|null $data;

    /**
     * @param  array<int, Link>  $links
     * @param  array<string, mixed>  $meta
     * @return self
     */
    public static function toOne(?ResourceIdentifier $data, array $links = [], array $meta = [])
    {
        return new self($data, $links, $meta);
    }

    /**
     * @param  array<int, ResourceIdentifier>  $data
     * @param  array<int, Link>  $links
     * @param  array<string, mixed>  $meta
     * @return self
     */
    public static function toMany(array $data, array $links = [], array $meta = [])
    {
        return new self($data, $links, $meta);
    }

    /**
     * @param  ResourceIdentifier|null|array<int, ResourceIdentifier>  $data
     * @param  array<int, Link>  $links
     * @param  array<string, mixed>  $meta
     */
    private function __construct(ResourceIdentifier|array|null $data, array $links = [], array $meta = [])
    {
        $this->data = $data;

        $this->links = $links;

        $this->meta = $meta;
    }

    /**
     * @return array{data: ResourceIdentifier|null|array<int, ResourceIdentifier>, meta?: stdClass, links?: stdClass}
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            ...$this->meta ? ['meta' => (object) $this->meta] : [],
            ...$this->links ? ['links' => (object) self::parseLinks($this->links)] : [],
        ];
    }
}
