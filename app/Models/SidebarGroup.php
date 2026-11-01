<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SidebarGroup extends Model
{
    protected $attributes = ['position' => 0, 'version' => 1];
    protected $fillable = ['name', 'position'];
    protected function casts(): array { return ['position' => 'integer', 'version' => 'integer']; }
    public function items() { return $this->hasMany(SidebarGroupItem::class)->orderBy('position')->orderBy('id'); }
}
