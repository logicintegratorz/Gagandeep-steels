<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class ProductSize extends Model
{
    protected $fillable=['size','base_price','difference','weight','cc','notes','manufacturer_price','godown_price'];
    public function product() { return $this->belongsTo(Product::class); }
}
