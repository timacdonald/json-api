<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use ReturnTypeWillChange;
use stdClass;

final class JsonApiServerImplementation implements JsonSerializable
{
    use Concerns\Meta;

    private string $version;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(string $version, array $meta = [])
    {
        $this->version = $version;

        $this->meta = $meta;
    }

    /**
     * @internal
     *
     * @return array{version: string, meta: stdClass}
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'version' => $this->version,
            'meta' => (object) $this->meta,
        ];
    }
}
