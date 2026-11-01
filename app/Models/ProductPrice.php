<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class ProductPrice extends Model
{
    protected $fillable = ['company', 'effective_date', 'purchase_price', 'retail_price'];
    protected function casts(): array { return ['effective_date' => 'date']; }
    public function product() { return $this->belongsTo(Product::class); }
}
