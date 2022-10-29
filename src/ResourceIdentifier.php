<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;

final class ResourceIdentifier implements JsonSerializable
{
    use Concerns\Meta;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $id;

    /**
     * @param string $type
     * @param string $id
     * @param array<string, mixed> $meta
     */
    public function __construct($type, $id, $meta = [])
    {
        $this->type = $type;

        $this->id = $id;

        $this->meta = $meta;
    }

    /**
     * @return array{type: string, id: string, meta: stdClass}
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'meta' => (object) $this->meta,
        ];
    }
}
