<?php

namespace Tests\Resources;

use Illuminate\Http\Request;
use Tests\Resources\ImageResource;
use Tests\Resources\LicenseResource;
use Tests\Resources\PostResource;
use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    protected function toAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
        ];
    }

    protected function toRelationships(Request $request): array
    {
        return [
            'posts' => fn () => PostResource::collection($this->posts),
            'license' => fn () => LicenseResource::make($this->license),
            'avatar' => fn () => ImageResource::make($this->avatar),
        ];
    }
}
