<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SidebarItemOrder extends Model
{
    protected $fillable = ['item_key', 'position'];
    protected function casts(): array { return ['position' => 'integer']; }
}
