<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property NestedResource $relation
 */
class BasicModel extends Model
{
    protected $guarded = [];

    protected $keyType = 'string';
}
