<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;

final class JsonApiServerImplementation implements JsonSerializable
{
    private string $version;

    /**
     * @var array<string, mixed>
     */
    private array $meta;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(string $version, array $meta = [])
    {
        $this->version = $version;

        $this->meta = $meta;
    }

    public function withMeta(array $meta): self
    {
        $this->meta = array_merge_recursive($this->meta, $meta);

        return $this;
    }

    /**
     * @return array{version: string, meta: stdClass}
     */
    public function jsonSerialize(): array
    {
        return [
            'version' => $this->version,
            'meta' => (object) $this->meta,
        ];
    }
}
