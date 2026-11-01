<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ScreenTimeEntry extends Model
{
    protected $fillable = ['user_id', 'tracked_on', 'seconds', 'first_seen_at', 'last_seen_at', 'last_active_at'];

    protected function casts(): array
    {
        return [
            'tracked_on' => 'date',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
