<?php
namespace App\Support;

final class LeadEmailFailure {
 public static function authenticationRejected(?string $error):bool {
  return (bool) preg_match('/\b535\b|5\.7\.8|BadCredentials|username and password not accepted|failed to authenticate|mail server rejected the SMTP login/i', $error ?? '');
 }

 public static function message(?string $error):string {
  if(self::authenticationRejected($error))return 'Email not sent: the mail server rejected the SMTP login. Ask your administrator to check the full username and app password in Lead settings.';
  if(preg_match('/timed out|timeout|connect|refused|unreachable|resolve|host not found/i',$error ?? ''))return 'Email not sent: CRM could not connect to the mail server. Check the SMTP host, port and network in Lead settings.';
  return 'Email sending could not be confirmed. Check the sender mailbox and SMTP settings before retrying.';
 }
}
