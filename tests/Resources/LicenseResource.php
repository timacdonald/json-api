<?php

declare(strict_types=1);

namespace Tests\Resources;

use Illuminate\Http\Request;
use TiMacDonald\JsonApi\JsonApiResource;

/**
 * @mixin \Tests\Models\BasicModel
 */
class LicenseResource extends JsonApiResource
{
    public function toAttributes(Request $request): array
    {
        return [
            'key' => $this->key,
        ];
    }

    protected function toRelationships(Request $request): array
    {
        return [
            'user' => fn () => UserResource::make($this->user),
        ];
    }
}
