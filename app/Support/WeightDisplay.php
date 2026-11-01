<?php
namespace App\Support;
final class WeightDisplay {
 public static function parts(?string $value): array {
  $text=trim($value ?? '');
  $n='([+-]?(?:\d+(?:\.\d*)?|\.\d+))';
  if (preg_match('/^'.$n.'(?:\s*kg\s*\/\s*(?:mtr|m|metre|meter))?$/i',$text,$m) || preg_match('/^'.$n.'\s*kg\s*\/\s*(?:mtr|m|metre|meter)\b/i',$text,$m)) {
   return [$m[1],self::number((float)$m[1]*0.3048)];
  }
  if (preg_match('/^'.$n.'\s*kg\s*\/\s*(?:feet|ft|foot)$/i',$text,$m)) return [self::number((float)$m[1]/0.3048),$m[1]];
  return ['', ''];
 }
 public static function number(float $value): string { return rtrim(rtrim(number_format($value,6,'.',''),'0'),'.'); }
 public static function format(?string $value): string {
  [$m,$f]=self::parts($value);
  return $m !== '' ? $m.' kg/mtr - '.$f.' kg/feet' : ($value ?? '');
 }
}
