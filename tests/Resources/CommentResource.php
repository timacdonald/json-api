<?php

namespace Tests\Resources;

use Illuminate\Http\Request;
use Tests\Resources\BasicJsonApiResource;
use TiMacDonald\JsonApi\JsonApiResource;

class CommentResource extends JsonApiResource
{
    protected function toAttributes(Request $request): array
    {
        return [
            'content' => $this->content,
        ];
    }

    protected function toRelationships(Request $request): array
    {
        return [
            'post' => fn () => PostResource::make($this->post),
            'likes' => fn () => BasicJsonApiResource::collection($this->likes),
        ];
    }
}
