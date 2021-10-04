<?php

namespace Tests\Resources;

use Illuminate\Http\Request;
use Tests\Resources\CommentResource;
use Tests\Resources\ImageResource;
use TiMacDonald\JsonApi\JsonApiResource;

class PostResource extends JsonApiResource
{
    protected function toAttributes(Request $request): array
    {
        return [
            'title' => $this->title,
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
