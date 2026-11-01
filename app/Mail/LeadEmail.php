<?php
namespace App\Mail;
use Illuminate\Mail\Mailable;
final class LeadEmail extends Mailable {
 public function __construct(public string $recipient,public string $mailSubject,public string $bodyText,public string $senderAddress,public string $senderName){}
 public function build(){return $this->from($this->senderAddress,$this->senderName)->to($this->recipient)->subject($this->mailSubject)->text('leads.email',['bodyText'=>$this->bodyText]);}
}
