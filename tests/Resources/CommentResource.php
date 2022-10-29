<?php

declare(strict_types=1);

namespace Tests\Resources;

use Illuminate\Http\Request;
use TiMacDonald\JsonApi\JsonApiResource;

/**
 * @mixin \Tests\Models\BasicModel
 */
class CommentResource extends JsonApiResource
{
    public function toAttributes(Request $request): array
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
