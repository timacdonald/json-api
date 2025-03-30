<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use stdClass;

final class ServerImplementation implements JsonSerializable
{
    use Concerns\Meta;

    private string $version;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(string $version, array $meta = [])
    {
        $this->version = $version;

        $this->meta = $meta;
    }

    /**
     * @return array{version: string, meta?: stdClass}
     */
    public function jsonSerialize(): array
    {
        return [
            'version' => $this->version,
            ...$this->meta ? ['meta' => (object) $this->meta] : [],
        ];
    }
}
