<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class Product extends Model
{
    protected $fillable = ['name', 'item_code', 'hsn_sac', 'item_type', 'category', 'unit', 'tax_type', 'description', 'active', 'picture_path'];
    protected function casts(): array { return ['active' => 'boolean', 'version' => 'integer']; }
    public function sizes() { return $this->hasMany(ProductSize::class); }
    public function prices() { return $this->hasMany(ProductPrice::class); }
}
