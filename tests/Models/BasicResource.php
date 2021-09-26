<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property NestedResource $nested
 */
class BasicResource extends Model
{
    protected $guarded = [];

    protected $keyType = 'string';
}
