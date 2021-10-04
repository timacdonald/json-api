<?php

declare(strict_types=1);

namespace Tests\Resources;

use Closure;
use Illuminate\Http\Request;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;

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
            'posts' => fn (Request $request) => PostResource::collection($this->posts),
            'license' => fn (Request $request) => LicenseResource::make($this->license),
            'avatar' => fn (Request $request) => ImageResource::make($this->avatar),
        ];
    }
}
