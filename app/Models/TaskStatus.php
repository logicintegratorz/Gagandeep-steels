<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TaskStatus extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_completed' => 'boolean'];

    public static function ordered()
    {
        return static::query()->orderBy('position')->orderBy('id');
    }
}
