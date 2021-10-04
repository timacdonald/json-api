<?php

declare(strict_types=1);

namespace Tests\Resources;

use Illuminate\Http\Request;
use TiMacDonald\JsonApi\JsonApiResource;

class LicenseResource extends JsonApiResource
{
    protected function toAttributes(Request $request): array
    {
        return [
            'key' => $this->key,
        ];
    }

    protected function toRelationships(Request $request): array
    {
        return [
            'user' => UserResource::make($this->user),
        ];
    }
}
