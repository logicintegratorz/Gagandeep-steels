<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ContentDocument extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['attachment_size' => 'integer', 'version' => 'integer']; }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
