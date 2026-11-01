<?php
namespace Tests\Feature;

use App\Models\FreightRate;
use App\Models\Transporter;
use Tests\TestCase;

final class TransporterTest extends TestCase
{
    protected function setUp(): void { parent::setUp(); $this->signIn(); }
    private function details(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Punjab Transport', 'contact_person' => 'Demo Contact',
            'phone' => '+91 98765 43210', 'gst_number' => '03ABCDE1234F1Z5',
            'address' => 'Demo office, Gobindgarh, Punjab 147301',
        ], $overrides);
    }

    public function test_edit_updates_all_details_and_renames_linked_freight_rates(): void
    {
        $transporter = Transporter::create($this->details());
        $rate = FreightRate::create(['origin' => 'Gobindgarh', 'destination' => 'Delhi', 'price' => '1000', 'transporter' => $transporter->name]);
        $this->get(route('transporters.edit', $transporter))->assertOk()->assertSee('Save changes')->assertSee('Punjab Transport');
        $this->put(route('transporters.update', $transporter), $this->details([
            'name' => 'Updated Carrier', 'contact_person' => 'New Contact', 'phone' => '9876543210',
            'address' => 'New address', 'gst_number' => '', 'version' => 1,
        ]))->assertSessionHasNoErrors()->assertRedirect(route('transporters.index'));
        $this->assertDatabaseHas('transporters', ['id' => $transporter->id, 'name' => 'Updated Carrier', 'contact_person' => 'New Contact', 'phone' => '9876543210', 'address' => 'New address', 'gst_number' => null, 'version' => 2]);
        $this->assertSame('Updated Carrier', $rate->refresh()->transporter);
        $this->assertSame(2, $rate->version);
        $this->getJson(route('transporters.options'))->assertExactJson(['Updated Carrier']);
        $this->put(route('transporters.update', $transporter), $this->details(['version' => 1]))->assertSessionHasErrors('conflict');
        $this->delete(route('transporters.destroy', $transporter), ['version' => 1])->assertSessionHasErrors('conflict');
    }

    public function test_edit_allows_unchanged_name_but_rejects_duplicates_and_invalid_details(): void
    {
        $transporter = Transporter::create($this->details());
        Transporter::create($this->details(['name' => 'Other Carrier']));
        $this->put(route('transporters.update', $transporter), $this->details(['version' => 1]))->assertSessionHasNoErrors();
        $this->put(route('transporters.update', $transporter), $this->details(['name' => 'Other Carrier', 'version' => 2]))->assertSessionHasErrors('name');
        $this->put(route('transporters.update', $transporter), $this->details(['phone' => 'invalid', 'version' => 2]))->assertSessionHasErrors('phone');
        $this->assertSame('Punjab Transport', $transporter->refresh()->name);
    }

    public function test_delete_removes_unused_transporter_but_protects_linked_rates(): void
    {
        $unused = Transporter::create($this->details());
        $this->delete(route('transporters.destroy', $unused), ['version' => 1])->assertRedirect(route('transporters.index'));
        $this->assertDatabaseMissing('transporters', ['id' => $unused->id]);
        $this->getJson(route('transporters.options'))->assertExactJson([]);
        $used = Transporter::create($this->details(['name' => 'Used Carrier']));
        FreightRate::create(['origin' => 'Gobindgarh', 'destination' => 'Delhi', 'price' => '1000', 'transporter' => $used->name]);
        $this->delete(route('transporters.destroy', $used), ['version' => 1])->assertSessionHasErrors('transporter');
        $this->assertDatabaseHas('transporters', ['id' => $used->id]);
        $this->assertDatabaseCount('freight_rates', 1);
    }

    public function test_added_transporter_is_available_without_any_freight_rates(): void
    {
        $this->post(route('transporters.store'), $this->details(['name' => '  Punjab Transport  ']))
            ->assertRedirect(route('transporters.index'));
        $this->assertDatabaseHas('transporters', ['name' => 'Punjab Transport']);
        $this->assertDatabaseHas('transporters', ['contact_person' => 'Demo Contact', 'phone' => '+919876543210', 'gst_number' => '03ABCDE1234F1Z5', 'address' => 'Demo office, Gobindgarh, Punjab 147301']);
        $this->get(route('rates.index'))->assertOk()->assertSee('Punjab Transport')
            ->assertViewHas('transporterCount', 1);
        $this->get(route('transporters.index'))->assertOk()->assertSee('Punjab Transport');
        $this->getJson(route('transporters.options'))->assertOk()->assertExactJson(['Punjab Transport']);
    }

    public function test_duplicate_blank_and_overlong_names_are_rejected(): void
    {
        Transporter::create(['name' => 'Punjab Transport']);
        foreach (['punjab transport', ' Punjab Transport ', '', '   ', str_repeat('a', 151)] as $name) {
            $this->post(route('transporters.store'), $this->details(['name' => $name]))->assertSessionHasErrors('name');
        }
        $this->assertDatabaseCount('transporters', 1);
    }

    public function test_unregistered_names_cannot_be_saved_on_freight_form(): void
    {
        $payload = ['origin' => 'Gobindgarh', 'destination' => 'Delhi', 'price' => '1200', 'transporter' => 'Unknown Transport'];
        $this->post(route('rates.store'), $payload)->assertSessionHasErrors('transporter');
        $this->assertDatabaseCount('freight_rates', 0);
        Transporter::create(['name' => 'Unknown Transport']);
        $this->post(route('rates.store'), $payload)->assertSessionHasNoErrors();
        $rate = FreightRate::firstOrFail();
        $this->get(route('rates.edit', $rate))->assertOk()->assertSee('value="Unknown Transport" selected', false);
        $this->put(route('rates.update', $rate), array_merge($payload, ['version' => 1, 'transporter' => 'Unregistered']))
            ->assertSessionHasErrors('transporter');
        $this->assertSame('Unknown Transport', $rate->refresh()->transporter);
    }

    public function test_directory_search_escapes_wildcards_and_names_are_safe(): void
    {
        Transporter::create(['name' => '100% Transport']);
        Transporter::create(['name' => '1000 Transport']);
        Transporter::create(['name' => '<script>alert(1)</script>']);
        $this->get(route('transporters.index', ['search' => '%']))->assertOk()
            ->assertViewHas('transporters', fn ($rows) => $rows->total() === 1);
        $this->get(route('transporters.index'))->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_migration_imports_existing_names_without_changing_rates(): void
    {
        FreightRate::create(['origin' => 'Gobindgarh', 'destination' => 'Delhi', 'price' => '1250', 'transporter' => 'Existing Partner']);
        $migration = require database_path('migrations/2026_09_30_000002_create_transporters_table.php');
        $migration->down();
        $migration->up();
        $detailsMigration = require database_path('migrations/2026_09_30_000003_add_transporter_contact_details.php');
        $detailsMigration->up();
        $this->assertDatabaseHas('transporters', ['name' => 'Existing Partner']);
        $this->assertDatabaseHas('transporters', ['name' => 'Existing Partner', 'phone' => null]);
        $this->assertDatabaseHas('freight_rates', ['transporter' => 'Existing Partner', 'price' => '1250.00']);
    }

    public function test_contact_validation_preserves_input_and_gst_is_optional(): void
    {
        $this->from(route('transporters.index'))->post(route('transporters.store'), $this->details([
            'contact_person' => '', 'phone' => 'bad phone', 'address' => '', 'gst_number' => 'invalid',
        ]))->assertSessionHasErrors(['contact_person', 'phone', 'address', 'gst_number'])
            ->assertSessionHasInput('name', 'Punjab Transport');
        $this->assertDatabaseCount('transporters', 0);
        $this->post(route('transporters.store'), $this->details(['gst_number' => '']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('transporters', ['gst_number' => null]);
    }

    public function test_search_includes_contact_details_and_gst_is_normalized(): void
    {
        $this->post(route('transporters.store'), $this->details(['gst_number' => '03abcde1234f1z5']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('transporters', ['gst_number' => '03ABCDE1234F1Z5']);
        foreach (['Demo Contact', '98765', '03ABCDE', '147301'] as $search) {
            $this->get(route('transporters.index', ['search' => $search]))->assertOk()
                ->assertViewHas('transporters', fn ($rows) => $rows->total() === 1);
        }
    }
}
