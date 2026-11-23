<?php

namespace Tests\Feature;

use App\Models\JobRole;
use App\Models\Lead;
use App\Models\LeadConnection;
use Tests\TestCase;

final class ContactSeparationTest extends TestCase
{
    public function test_manual_contacts_and_platform_leads_are_shown_in_their_own_workspaces(): void
    {
        $admin = $this->signIn();
        $jobRole = JobRole::query()->value('name');
        $this->assertNotNull($jobRole);

        $this->post(route('leads.store'), [
            'name' => 'Manual Contact Buyer',
            'company' => 'Manual Steel Works',
            'phone' => '9876543210',
            'email' => 'manual@example.test',
            'job_role' => $jobRole,
            'stage' => 'New',
            'priority' => 'Normal',
        ])->assertRedirect(route('contacts.index'));

        $contact = Lead::where('name', 'Manual Contact Buyer')->firstOrFail();
        $this->assertNull($contact->connection_id);
        $this->assertNotNull($contact->contact_saved_at);
        $this->assertSame('Manual contact', $contact->source);
        $this->assertSame($admin->id, $contact->created_by);
        $this->assertSame($admin->id, $contact->owner_id);

        $connection = LeadConnection::create([
            'provider' => 'Audit platform feed',
            'enabled' => true,
            'owner_id' => $admin->id,
        ]);
        $platformLead = Lead::create([
            'name' => 'Platform Lead Buyer',
            'connection_id' => $connection->id,
            'external_id' => 'audit-1',
            'owner_id' => $admin->id,
            'source' => 'Audit platform feed',
            'stage' => 'New',
            'priority' => 'Normal',
        ]);

        $this->get(route('contacts.index'))
            ->assertOk()
            ->assertSee('Manual Contact Buyer')
            ->assertDontSee('Platform Lead Buyer');

        $this->get(route('contacts.index', ['job_role' => $jobRole]))
            ->assertOk()
            ->assertSee('Manual Contact Buyer')
            ->assertSee($jobRole)
            ->assertViewHas('contacts', fn ($contacts) => $contacts->total() === 1);

        $this->post(route('leads.stage', $contact), [
            'stage' => 'Paperwork',
            'version' => $contact->version,
        ])->assertSessionHasNoErrors();

        $this->get(route('leads.pipeline'))
            ->assertOk()
            ->assertSee('Manual Contact Buyer')
            ->assertSee('Paperwork');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Platform Lead Buyer')
            ->assertDontSee('Manual Contact Buyer');

        $this->get(route('global-search.index', ['q' => 'Buyer']))
            ->assertOk()
            ->assertSee('Manual Contact Buyer')
            ->assertSee('Manual contact')
            ->assertSee('Platform Lead Buyer')
            ->assertSee('Platform lead');

        $this->assertDatabaseHas('leads', ['id' => $platformLead->id, 'connection_id' => $connection->id]);
    }
}
