<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;

final class ResourceIdentifier implements JsonSerializable
{
    use Concerns\Meta;

    private string $type;

    private string $id;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(string $type, string $id, array $meta = [])
    {
        $this->type = $type;

        $this->id = $id;

        $this->meta = $meta;
    }

    /**
     * @return array{type: string, id: string, meta?: stdClass}
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            ...$this->meta ? ['meta' => (object) $this->meta] : [],
        ];
    }
}
