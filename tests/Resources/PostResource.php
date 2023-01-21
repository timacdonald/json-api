<?php

declare(strict_types=1);

namespace Tests\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

/**
 * @mixin \Tests\Models\BasicModel
 */
class PostResource extends JsonApiResource
{
    public function toAttributes($request)
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
        ];
    }

    public function toRelationships($request)
    {
        return [
            'author' => fn () => UserResource::make($this->author),
            'featureImage' => fn () => ImageResource::make($this->feature_image),
            'comments' => fn () => CommentResource::collection($this->comments),
        ];
    }

    public static function collection($resource)
    {
        return parent::collection($resource);
    }
}
