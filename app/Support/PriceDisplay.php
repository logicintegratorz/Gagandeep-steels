<?php
namespace App\Support;

final class PriceDisplay
{
    public static function format(?string $value): string
    {
        $text = $value ?? '';
        return preg_match('/^[+-]?\d+\.0+$/D', $text)
            ? substr($text, 0, strpos($text, '.'))
            : $text;
    }
}
