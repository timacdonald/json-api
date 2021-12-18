<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;

class JsonApiServerImplementation implements JsonSerializable
{
    private string $version;

    private array $meta;

    public function __construct(string $version, array $meta = [])
    {
        $this->version = $version;

        $this->meta = $meta;
    }

    /**
     * @internal
     */
    public function jsonSerialize(): array
    {
        return [
            'version' => $this->version,
            'meta' => (object) $this->meta,
        ];
    }
}
