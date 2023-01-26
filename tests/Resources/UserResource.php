<?php

declare(strict_types=1);

namespace Tests\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

/**
 * @mixin \Tests\Models\BasicModel
 */
class UserResource extends JsonApiResource
{
    public function toAttributes($request)
    {
        return [
            'name' => $this->name,
        ];
    }

    public function toRelationships($request)
    {
        return [
            'posts' => fn () => PostResource::collection($this->posts),
            'license' => fn () => LicenseResource::make($this->license),
            'avatar' => fn () => ImageResource::make($this->avatar),
        ];
    }
}
