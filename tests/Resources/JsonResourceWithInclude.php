<?php

declare(strict_types=1);

namespace Tests\Resources;

use Illuminate\Http\Request;
use TiMacDonald\JsonApi\JsonApiResource;

class JsonResourceWithInclude extends JsonApiResource
{
    public function toRelationships(Request $request): array
    {
        return [
            'nested' => fn () => JsonResourceWithInclude::make($this->nested),
            'nesteds' => fn () => JsonResourceWithInclude::collection($this->nesteds),
        ];
    }
}
