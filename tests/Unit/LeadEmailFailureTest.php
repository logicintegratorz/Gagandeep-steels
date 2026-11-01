<?php
namespace Tests\Unit;

use App\Support\LeadEmailFailure;
use PHPUnit\Framework\TestCase;

final class LeadEmailFailureTest extends TestCase {
 public function test_gmail_authentication_error_is_recognized_without_exposing_raw_server_text():void {
  $raw='Failed to authenticate on SMTP server with username "sender@example.test": 535-5.7.8 Username and Password not accepted';
  $this->assertTrue(LeadEmailFailure::authenticationRejected($raw));
  $this->assertStringContainsString('SMTP login',LeadEmailFailure::message($raw));
  $this->assertStringNotContainsString('sender@example.test',LeadEmailFailure::message($raw));
  $this->assertTrue(LeadEmailFailure::authenticationRejected(LeadEmailFailure::message($raw)));
 }
}
