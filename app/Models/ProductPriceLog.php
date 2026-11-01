<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class ProductPriceLog extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected function casts(): array { return ['effective_date'=>'date', 'changed_at'=>'datetime']; }
}
