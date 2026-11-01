<?php

namespace Tests\Feature;

use App\Models\ScreenTimeEntry;
use App\Models\CrmAuditEvent;
use Tests\TestCase;

final class ScreenTimeTest extends TestCase
{
    public function test_focused_heartbeats_and_pause_store_screen_time_for_admin_reporting(): void
    {
        $user = $this->signIn();

        $this->post(route('screen-time.heartbeat'), ['state' => 'active'])->assertOk();
        $entry = ScreenTimeEntry::query()->firstOrFail();
        $entry->update(['last_seen_at' => now('Asia/Kolkata')->subSeconds(20)]);

        $this->post(route('screen-time.heartbeat'), ['state' => 'active'])
            ->assertOk()
            ->assertJsonPath('recording', true);

        $entry->refresh();
        $this->assertSame($user->id, $entry->user_id);
        $this->assertSame(now('Asia/Kolkata')->toDateString(), $entry->tracked_on->toDateString());
        $this->assertGreaterThanOrEqual(20, $entry->seconds);
        $this->assertLessThanOrEqual(21, $entry->seconds);

        $entry->refresh()->update(['last_seen_at' => now('Asia/Kolkata')->subSeconds(12)]);
        $this->post(route('screen-time.heartbeat'), ['state' => 'pause'])
            ->assertOk()
            ->assertJsonPath('recording', false);

        $entry->refresh();
        $this->assertNull($entry->last_seen_at);
        $this->assertGreaterThanOrEqual(32, $entry->seconds);
        $this->assertLessThanOrEqual(35, $entry->seconds);
        $this->assertSame(0, CrmAuditEvent::where('route_name', 'screen-time.heartbeat')->count());
        $this->get(route('screen-time.index'))->assertOk()->assertSee('Screen time report')->assertSee($user->name);
    }
}
