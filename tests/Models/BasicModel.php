<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property string $content
 * @property string $key
 * @property string $name
 * @property string $title
 * @property string $url
 * @property array $posts
 * @property self $author
 * @property self $license
 * @property self $feature_image
 * @property self $avatar
 * @property self|array $child
 * @property self $post
 * @property self $user
 * @property Collection<self> $likes
 * @property Collection<self> $comments
 */
class BasicModel extends Model
{
    protected $guarded = [];

    protected $keyType = 'string';
}
