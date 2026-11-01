<?php
namespace Tests\Feature;

use App\Models\{Lead, LeadActivity, Role, User};
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    public function test_dashboard_requires_login(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_administrator_sees_dashboard_and_existing_workspaces(): void
    {
        $this->signIn();
        $this->get(route('home'))->assertOk()->assertViewIs('dashboard')
            ->assertSee('Total Leads')->assertSee('Total Tasks')->assertSee('Opportunities')->assertSee('Reminders')->assertSee('Call Details')->assertSee('Total leads')
            ->assertSee('Quotation')->assertSee('SMS')->assertSee('WhatsApp')->assertSee('Email')->assertSee('Call')
            ->assertSee('class="dash-top-stack"', false)->assertSeeInOrder(['data-calculator-open','data-theme-toggle','notification-bell'], false)
            ->assertDontSee('data-theme-option', false)
            ->assertSee('<span class="dash-count dash-summary-count">0</span>', false)
            ->assertSee('Overdue Tasks')->assertSee('Initial Contact')->assertSee('All Reminders')
            ->assertSee(route('rates.index'))->assertSee(route('access.index'));
    }

    public function test_dashboard_does_not_expose_restricted_navigation(): void
    {
        $role = Role::create(['name' => 'Dashboard only', 'permissions' => []]);
        $user = User::create(['name' => 'Limited user', 'email' => 'limited@example.test', 'password' => 'Testing1234', 'active' => true, 'role_id' => $role->id]);
        $this->signIn($user);
        $this->get(route('home'))->assertOk()->assertViewIs('dashboard')
            ->assertDontSee(route('rates.index'))->assertDontSee(route('access.index'))
            ->assertDontSee(route('transporters.index'))->assertSee(route('notifications.index'));
        $this->get(route('dashboard.contacts.export'))->assertForbidden();
    }

    public function test_excel_backup_contains_all_matching_leads_including_unsaved_inquiries(): void
    {
        $admin=$this->signIn();
        for($i=1;$i<=12;$i++)Lead::create(['name'=>'Saved buyer '.$i,'company'=>'Matching Steel','owner_id'=>$admin->id,'stage'=>'New','phone'=>'98765000'.$i,'contact_saved_at'=>now()]);
        Lead::create(['name'=>'Unsaved buyer','company'=>'Matching Steel','owner_id'=>$admin->id,'stage'=>'New']);
        Lead::create(['name'=>'Other saved buyer','company'=>'Other Steel','owner_id'=>$admin->id,'stage'=>'New','contact_saved_at'=>now()]);

        $response=$this->get(route('dashboard.contacts.export',['filters'=>['company'=>'Matching']]));
        $response->assertOk()->assertDownload();
        $path=$response->baseResponse->getFile()->getPathname();
        $zip=new \ZipArchive();
        $this->assertTrue($zip->open($path)===true);
        $sheet=$zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotFalse($sheet);
        $this->assertStringContainsString('Saved buyer 12',$sheet);
        $this->assertStringContainsString('Unsaved buyer',$sheet);
        $this->assertStringNotContainsString('Other saved buyer',$sheet);
        $this->assertSame(14,substr_count($sheet,'<row r='));
        $zip->close();
    }

    public function test_platform_inquiry_appears_in_contacts_table_without_separate_untouched_section():void
    {
        $this->signIn();
        Lead::create(['name'=>'Platform demo buyer','source'=>'IndiaMART','stage'=>'New']);
        $this->get(route('home'))->assertOk()->assertSee('Platform demo buyer')->assertSee('IndiaMART')
            ->assertSee('Untouched')->assertDontSee('id="platform-leads-title"',false);
    }

    public function test_contacts_table_has_a_call_action_for_each_lead_with_a_phone(): void
    {
        $admin = $this->signIn();
        $callable = Lead::create(['name' => 'Callable buyer', 'phone' => '+91 98765-43210', 'owner_id' => $admin->id, 'stage' => 'New']);
        Lead::create(['name' => 'No phone buyer', 'owner_id' => $admin->id, 'stage' => 'New']);

        $page = $this->get(route('home'))->assertOk()
            ->assertSee('data-dashboard-call-dialog', false)
            ->assertSee('data-dashboard-call-open', false)
            ->assertSee(route('leads.call.start', $callable), false)
            ->assertSee(route('leads.call.qr', $callable), false)
            ->assertSee(route('leads.call.outcome', $callable), false)
            ->assertSee('data-call-phone="+919876543210"', false);

        $this->assertSame(1, substr_count($page->getContent(), 'data-dashboard-call-open'));
        $this->postJson(route('leads.call.start', $callable), ['dashboard' => 1])->assertOk()->assertJson(['locked' => false]);
        $this->assertNull(session('active_lead_call_id'));
        $this->get(route('home'))->assertOk()->assertSee('Callable buyer');
    }

    public function test_dashboard_metric_links_open_only_matching_leads(): void
    {
        $admin = $this->signIn();
        Lead::create(['name'=>'Today new buyer','owner_id'=>$admin->id,'stage'=>'New']);
        Lead::create(['name'=>'Converted buyer','owner_id'=>$admin->id,'stage'=>'Won','created_at'=>now()->subDays(2)]);

        $this->get(route('home'))->assertOk()
            ->assertSee(route('dashboard.metric', 'uncontacted-leads'))
            ->assertSee('Expand list')->assertSee('aria-expanded="false"', false);
        $this->get(route('dashboard.metric', 'uncontacted-leads'))->assertOk()
            ->assertSee('Today new buyer')->assertDontSee('Converted buyer');
        $this->get(route('dashboard.metric', 'converted-leads'))->assertOk()
            ->assertSee('Converted buyer')->assertDontSee('Today new buyer');
    }

    public function test_metric_pages_use_compact_previous_and_next_links(): void
    {
        $admin = $this->signIn();
        for ($index = 1; $index <= 26; $index++) {
            Lead::create(['name' => 'Page lead '.$index, 'owner_id' => $admin->id, 'stage' => 'New']);
        }

        $this->get(route('dashboard.metric', 'uncontacted-leads'))->assertOk()
            ->assertSee('class="metric-pagination"', false)->assertSee('Next →')
            ->assertDontSee('pagination::tailwind');
        $this->get(route('dashboard.metric', ['metric' => 'uncontacted-leads', 'page' => 2]))->assertOk()
            ->assertSee('← Previous')->assertSee('Page 2 of 2');
    }

    public function test_call_metric_pages_hide_generic_copy_and_filter_by_company_and_user(): void
    {
        $admin = $this->signIn();
        $role = Role::create(['name' => 'Caller', 'permissions' => []]);
        $caller = User::create(['name' => 'Sales Caller', 'email' => 'caller@example.test', 'password' => 'Testing1234', 'active' => true, 'role_id' => $role->id]);
        $first = Lead::create(['name' => 'First buyer', 'company' => 'Alpha Steel', 'stage' => 'New']);
        $second = Lead::create(['name' => 'Second buyer', 'company' => 'Beta Metals', 'stage' => 'New']);
        $older = Lead::create(['name' => 'Older buyer', 'company' => 'Alpha Steel', 'stage' => 'New']);
        LeadActivity::create(['lead_id' => $first->id, 'actor' => $caller->name, 'actor_id' => $caller->id, 'kind' => 'Call log', 'action' => 'Incoming call', 'details' => 'Alpha inquiry']);
        LeadActivity::create(['lead_id' => $second->id, 'actor' => $admin->name, 'actor_id' => $admin->id, 'kind' => 'Call log', 'action' => 'Incoming call', 'details' => 'Beta inquiry']);
        LeadActivity::create(['lead_id' => $older->id, 'actor' => $caller->name, 'actor_id' => $caller->id, 'kind' => 'Call log', 'action' => 'Incoming call', 'details' => 'Older inquiry', 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);

        $this->get(route('dashboard.metric', ['metric' => 'incoming-calls', 'company' => 'Alpha Steel']))
            ->assertOk()->assertSee('First buyer')->assertDontSee('Second buyer')
            ->assertSee('Sales Caller (Caller)')->assertDontSee('BUSINESS OVERVIEW')
            ->assertDontSee('Records included in this dashboard count');
        $this->get(route('dashboard.metric', ['metric' => 'incoming-calls', 'user' => $admin->id]))
            ->assertOk()->assertSee('Second buyer')->assertDontSee('First buyer');
        $this->get(route('dashboard.metric', ['metric' => 'incoming-calls', 'from' => now()->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk()->assertSee('From')->assertSee('To')->assertSee('First buyer')->assertSee('Second buyer')->assertDontSee('Older buyer');
        $this->get(route('dashboard.metric', ['metric' => 'incoming-calls', 'from' => now()->subDays(2)->toDateString(), 'to' => now()->subDays(2)->toDateString(), 'user' => $caller->id]))
            ->assertOk()->assertSee('Older buyer')->assertDontSee('First buyer')->assertDontSee('Second buyer');
    }
}
