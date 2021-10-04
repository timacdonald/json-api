<?php

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
