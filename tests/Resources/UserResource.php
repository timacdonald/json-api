<?php

declare(strict_types=1);

namespace Tests\Resources;

use Illuminate\Database\Eloquent\Model;
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
            'posts' => fn (Request $request): JsonApiResourceCollection => PostResource::collection($this->posts),
            'license' => fn (Request $request): LicenseResource => LicenseResource::make($this->license),
            'avatar' => fn (Request $request): ?ImageResource => optional($this->avatar, fn (Model $model) => ImageResource::make($model)),
        ];
    }
}
