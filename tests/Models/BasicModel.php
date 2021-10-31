<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array $posts
 * @property self $author
 * @property self $license
 * @property self $feature_image
 * @property self $avatar
 * @property self|array $child
 */
class BasicModel extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $keyType = 'string';
}
