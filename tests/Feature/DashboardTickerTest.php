<?php
namespace Tests\Feature;
use App\Models\{CrmNotification,NotificationBroadcast};
use Illuminate\Support\Str;
use Tests\TestCase;
final class DashboardTickerTest extends TestCase {
 public function test_ticker_only_shows_visible_delivered_broadcasts(): void {
  $user=$this->signIn();
  foreach (['Visible announcement','Recalled announcement','Dismissed announcement','Undelivered announcement'] as $title) {
   $broadcast=NotificationBroadcast::create(['sender_id'=>$user->id,'submission_key'=>(string)Str::uuid(),'title'=>$title,'message'=>'Message body','audience'=>'all','recipient_count'=>1,'recalled_at'=>$title==='Recalled announcement'?now():null]);
   if($title!=='Undelivered announcement') CrmNotification::create(['user_id'=>$user->id,'broadcast_id'=>$broadcast->id,'title'=>$title,'message'=>'Message body','dismissed_at'=>$title==='Dismissed announcement'?now():null]);
  }
  $this->get('/')->assertOk()->assertSee('Visible announcement')->assertDontSee('Recalled announcement')->assertDontSee('Dismissed announcement')->assertDontSee('Undelivered announcement')->assertSee('announcement-track',false);
 }
}
