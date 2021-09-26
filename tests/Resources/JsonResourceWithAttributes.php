<?php

declare(strict_types=1);

namespace Tests\Resources;

use Illuminate\Http\Request;
use TiMacDonald\JsonApi\JsonApiResource;

class JsonResourceWithAttributes extends JsonApiResource
{
    public function toAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'location' => 'Melbourne',
        ];
    }
}
