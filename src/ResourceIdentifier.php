<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;

final class ResourceIdentifier implements JsonSerializable
{
    private string $id;

    private string $type;

    /**
     * @var array<string, mixed>
     */
    private array $meta;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(string $id, string $type, array $meta = [])
    {
        $this->id = $id;

        $this->type = $type;

        $this->meta = $meta;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
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
