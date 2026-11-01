<?php
namespace App\Support;

use App\Models\Lead;

final class LeadWhatsAppLink {
 public static function number(Lead $lead):?string {
  $number=preg_replace('/\D+/', '', (string)($lead->whatsapp ?: $lead->phone));
  if(strlen($number)===10 && $number[0]>='6')$number='91'.$number;
  elseif(strlen($number)===11 && str_starts_with($number,'0') && $number[1]>='6')$number='91'.substr($number,1);
  if(!preg_match('/^91[6-9]\d{9}$/',$number))return null;
  return $number;
 }
 public static function for(Lead $lead,string $message):?string {
  $number=self::number($lead);
  return $number?'https://wa.me/'.$number.'?text='.rawurlencode($message):null;
 }
}
