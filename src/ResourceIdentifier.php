<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;
use TiMacDonald\JsonApi\Concerns\Meta;

final class ResourceIdentifier implements JsonSerializable
{
    use Meta;

    private string $id;

    private string $type;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(string $id, string $type, array $meta = [])
    {
        $this->id = $id;

        $this->type = $type;

        $this->meta = $meta;
    }

    /**
     * @return array{id: string, type: string, meta: stdClass}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'meta' => (object) $this->meta,
        ];
    }
}
