<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class FreightRate extends Model
{
    protected $fillable = ['origin', 'destination', 'price', 'transporter', 'remarks'];
    protected function casts(): array { return ['price' => 'decimal:2', 'version' => 'integer']; }
    public function formattedPrice(): string { return self::formatInr($this->price); }
    public static function formatInr(string|float|int $value): string
    {
        [$whole, $fraction] = explode('.', number_format((float) $value, 2, '.', ''));
        if (strlen($whole) > 3) {
            $tail = substr($whole, -3);
            $head = substr($whole, 0, -3);
            $whole = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $head).','.$tail;
        }
        return '₹'.$whole.'.'.$fraction;
    }
}
