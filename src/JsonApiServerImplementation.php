<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use JsonSerializable;
use ReturnTypeWillChange;
use stdClass;

final class JsonApiServerImplementation implements JsonSerializable
{
    use Concerns\Meta;

    /**
     * @var string
     */
    private $version;

    /**
     * @param string $version
     * @param array<string, mixed> $meta
     */
    public function __construct($version, $meta = [])
    {
        $this->version = $version;

        $this->meta = $meta;
    }

    /**
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
