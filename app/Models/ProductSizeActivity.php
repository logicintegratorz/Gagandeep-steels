<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class ProductSizeActivity extends Model {
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['before_values'=>'array','after_values'=>'array','occurred_at'=>'datetime'];
}
