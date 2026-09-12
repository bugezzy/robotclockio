<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $description
 * @property int $rank
 */
#[Fillable(['name', 'description', 'rank'])]
class Role extends Model
{
    use HasUuids;

    public $timestamps = false;
}
