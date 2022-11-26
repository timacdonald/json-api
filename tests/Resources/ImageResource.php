<?php

declare(strict_types=1);

namespace Tests\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

/**
 * @mixin \Tests\Models\BasicModel
 */
class ImageResource extends JsonApiResource
{
    public function toAttributes($request): array
    {
        return [
            'url' => $this->url,
        ];
    }
}
