<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property \Tests\Models\NestedResource $nested
 */
class NestedResource extends Model
{
    protected $guarded = [];

    protected $keyType = 'string';
}
