<?php

declare(strict_types=1);

namespace Tests\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;

/**
 * @mixin \Tests\Models\BasicModel
 */
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
            'posts' => fn (): JsonApiResourceCollection => PostResource::collection($this->posts),
            'license' => fn (): LicenseResource => LicenseResource::make($this->license),
            'avatar' => fn (): ?ImageResource => optional($this->avatar, fn (Model $model) => ImageResource::make($model)),
        ];
    }
}
