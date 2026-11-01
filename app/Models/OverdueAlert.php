<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class OverdueAlert extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['overdue_at' => 'datetime']; }
}
