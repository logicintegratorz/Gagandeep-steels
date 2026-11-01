<?php
namespace Tests\Feature;
use App\Models\FreightRate;
use App\Models\Transporter;
use Tests\TestCase;
final class FreightRateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->signIn();
        Transporter::create(['name' => 'QA Transport']);
    }
    private function payload(array $overrides = []): array
    {
        return array_merge(['origin' => 'Gobindgarh', 'destination' => 'Baddi', 'price' => '1250.50', 'transporter' => 'QA Transport', 'remarks' => 'Test record'], $overrides);
    }
    public function test_rate_is_saved_and_visible(): void
    {
        $this->post(route('rates.store'), $this->payload())->assertRedirect(route('rates.index'));
        $this->assertDatabaseHas('freight_rates', ['origin' => 'Gobindgarh', 'price' => '1250.50']);
        $this->get(route('rates.index'))->assertOk()->assertSee('Gobindgarh')->assertSee('₹1,250.50');
    }
    public function test_invalid_prices_and_identical_routes_are_rejected(): void
    {
        foreach (['-10', '0', '1.999', '1e3'] as $price) {
            $this->post(route('rates.store'), $this->payload(['price' => $price]))->assertSessionHasErrors('price');
        }
        $this->post(route('rates.store'), $this->payload(['destination' => ' gobindgarh ']))->assertSessionHasErrors('destination');
        $this->assertDatabaseCount('freight_rates', 0);
    }
    public function test_updates_increment_version_and_stale_changes_are_rejected(): void
    {
        $rate = FreightRate::create($this->payload());
        $this->put(route('rates.update', $rate), $this->payload(['version' => 1, 'price' => '1500.25']))->assertSessionHasNoErrors();
        $this->assertSame(2, $rate->refresh()->version);
        $this->put(route('rates.update', $rate), $this->payload(['version' => 1, 'price' => '1.00']))->assertSessionHasErrors('conflict');
        $this->assertSame('1500.25', $rate->refresh()->price);
        $this->delete(route('rates.destroy', $rate), ['version' => 1])->assertSessionHasErrors('conflict');
        $this->assertDatabaseCount('freight_rates', 1);
        $this->delete(route('rates.destroy', $rate), ['version' => 2])->assertRedirect(route('rates.index'));
        $this->assertDatabaseCount('freight_rates', 0);
    }
    public function test_filters_sorting_and_pagination_are_applied_in_mysql(): void
    {
        FreightRate::create($this->payload(['destination' => 'Delhi', 'price' => '2000.00']));
        FreightRate::create($this->payload(['destination' => 'Jaipur', 'price' => '1000.00']));
        $this->get(route('rates.index', ['to' => 'Delhi']))->assertViewHas('rates', fn ($rows) => $rows->total() === 1 && $rows[0]->destination === 'Delhi');
        $this->get(route('rates.index', ['sort' => 'price_asc']))->assertViewHas('rates', fn ($rows) => $rows[0]->destination === 'Jaipur');
        for ($i = 0; $i < 8; $i++) { FreightRate::create($this->payload(['destination' => 'City '.$i])); }
        $this->get(route('rates.index', ['page' => 2]))->assertViewHas('rates', fn ($rows) => $rows->count() === 2 && $rows->total() === 10);
    }
    public function test_user_text_is_escaped_and_like_wildcards_are_literal(): void
    {
        FreightRate::create($this->payload(['remarks' => '<script>alert(1)</script>', 'origin' => '100% City']));
        FreightRate::create($this->payload(['origin' => '1000 City']));
        $this->get(route('rates.index', ['from' => '100%']))->assertViewHas('rates', fn ($rows) => $rows->total() === 1);
        $this->get(route('rates.index'))->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
    public function test_city_filters_match_only_the_start_of_each_city(): void
    {
        FreightRate::create($this->payload(['origin' => 'Ludhiana', 'destination' => 'Mandi Gobindgarh']));
        FreightRate::create($this->payload(['origin' => 'New Ludhiana', 'destination' => 'New Mandi Gobindgarh']));

        foreach (['from' => 'ludh', 'to' => 'man'] as $filter => $prefix) {
            $this->get(route('rates.index', [$filter => ' '.$prefix.' ']))
                ->assertOk()->assertViewHas('rates', fn ($rows) => $rows->total() === 1 && $rows[0]->origin === 'Ludhiana');
        }
        foreach ([['from', 'dhiana'], ['to', 'gobind'], ['to', 'Gobindgarh']] as [$filter, $suffix]) {
            $this->get(route('rates.index', [$filter => $suffix]))
                ->assertOk()->assertViewHas('rates', fn ($rows) => $rows->total() === 0);
        }
        $this->get(route('rates.index', ['from' => 'Ludh', 'to' => 'Man']))
            ->assertViewHas('rates', fn ($rows) => $rows->total() === 1);
        $this->get(route('rates.index', ['from' => '', 'to' => '']))
            ->assertViewHas('rates', fn ($rows) => $rows->total() === 2);
        $csv = $this->get(route('rates.export', ['to' => 'Man']))->assertOk()->streamedContent();
        $this->assertStringContainsString('Mandi Gobindgarh', $csv);
        $this->assertStringNotContainsString('New Mandi Gobindgarh', $csv);
    }
    public function test_csv_exports_filtered_records_and_neutralizes_formulas(): void
    {
        FreightRate::create($this->payload(['remarks' => '=1+1']));
        FreightRate::create($this->payload(['destination' => 'Delhi', 'remarks' => 'excluded-record']));
        $response = $this->get(route('rates.export', ['to' => 'Baddi']))->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringNotContainsString('excluded-record', $csv);
        $this->assertStringContainsString('1250.50', $csv);
    }
}
