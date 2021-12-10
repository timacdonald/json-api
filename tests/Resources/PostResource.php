<?php

declare(strict_types=1);

namespace Tests\Resources;

use Illuminate\Http\Request;
use TiMacDonald\JsonApi\JsonApiResource;

/**
 * @mixin \Tests\Models\BasicModel
 */
class PostResource extends JsonApiResource
{
    protected function toAttributes(Request $request): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
        ];
    }

    protected function toRelationships(Request $request): array
    {
        return [
            'author' => fn () => UserResource::make($this->author),
            'featureImage' => fn () => ImageResource::make($this->feature_image),
            'comments' => fn () => CommentResource::collection($this->comments),
        ];
    }
}
