<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SidebarGroupItem extends Model
{
    protected $fillable = ['sidebar_group_id', 'item_key', 'position'];
    protected function casts(): array { return ['position' => 'integer']; }
    public function group() { return $this->belongsTo(SidebarGroup::class, 'sidebar_group_id'); }
}
