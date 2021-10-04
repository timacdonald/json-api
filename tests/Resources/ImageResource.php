<?php

namespace Tests\Resources;

use Illuminate\Http\Request;
use TiMacDonald\JsonApi\JsonApiResource;

class ImageResource extends JsonApiResource
{
    protected function toAttributes(Request $request): array
    {
        return [
            'url' => $this->url,
        ];
    }
}
