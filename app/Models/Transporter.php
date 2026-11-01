<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Transporter extends Model
{
    protected $fillable = ['name', 'contact_person', 'phone', 'gst_number', 'address'];
    protected function casts(): array { return ['version' => 'integer']; }
}
