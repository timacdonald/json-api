<?php

declare(strict_types=1);

namespace Tests\Resources;

use Illuminate\Http\Request;
use TiMacDonald\JsonApi\JsonApiResource;

/**
 * @mixin \Tests\Models\BasicModel
 */
class UserResource extends JsonApiResource
{
    public function toAttributes($request): array
    {
        return [
            'name' => $this->name,
        ];
    }

    public function toRelationships($request): array
    {
        return [
            'posts' => fn () => PostResource::collection($this->posts),
            'license' => fn () => LicenseResource::make($this->license),
            'avatar' => fn () => ImageResource::make($this->avatar),
        ];
    }
}
