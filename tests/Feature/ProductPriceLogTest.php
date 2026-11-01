<?php
namespace Tests\Feature;
use App\Models\{Product, ProductPriceLog, Role, User};
use Tests\TestCase;
final class ProductPriceLogTest extends TestCase
{
    protected function setUp(): void { parent::setUp(); $this->signIn(); }
    private function data(array $overrides=[]): array { return array_merge(['name'=>'Wire','item_code'=>'W','unit'=>'Kg','item_type'=>'Product','active'=>1,'company'=>'Alpha Steel','effective_date'=>now()->toDateString(),'purchase_price'=>'500 + freight','retail_price'=>'On request'], $overrides); }
    public function test_same_day_changes_remain_in_log_and_unchanged_saves_do_not_duplicate(): void
    {
        $this->post(route('products.store'),$this->data())->assertSessionHasNoErrors();
        $p=Product::firstOrFail();
        $this->put(route('products.update',$p),$this->data(['version'=>1,'purchase_price'=>'Negotiable']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('product_prices',1);
        $this->assertDatabaseCount('product_price_logs',2);
        $this->assertDatabaseHas('product_price_logs',['event'=>'Changed','old_purchase_price'=>'500 + freight','purchase_price'=>'Negotiable','changed_by'=>'Test Administrator']);
        $this->put(route('products.update',$p),$this->data(['version'=>2,'purchase_price'=>'Negotiable']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('product_price_logs',2);
        $this->get(route('products.logs'))->assertOk()->assertSee('Global log sheet')->assertSee('Wire rate history')->assertSee('Alpha Steel')->assertSee('On request')->assertSee('Product rate')->assertSee('Party name');
    }
    public function test_company_and_both_date_filters_limit_results(): void
    {
        $this->post(route('products.store'),$this->data(['effective_date'=>now()->subDays(2)->toDateString()]));
        $this->post(route('products.store'),$this->data(['company'=>'Beta Steel']));
        $this->get(route('products.logs',['company'=>'Alpha','from'=>now()->subDays(2)->toDateString(),'to'=>now()->subDays(2)->toDateString(),'date_basis'=>'effective_date']))->assertOk()->assertViewHas('logs',fn($logs)=>$logs->total()===1);
        $this->get(route('products.logs',['company'=>'Alpha','from'=>now()->toDateString(),'date_basis'=>'changed_at']))->assertViewHas('logs',fn($logs)=>$logs->total()===1);
        $this->get(route('products.logs',['to'=>now()->subDay()->toDateString()]))->assertOk()->assertViewHas('logs',fn($logs)=>$logs->total()===0);
        $this->get(route('products.logs',['date'=>now()->toDateString()]))->assertOk()->assertViewHas('logs',fn($logs)=>$logs->total()===2);
        $this->get(route('products.logs',['date'=>now()->subDay()->toDateString()]))->assertOk()->assertViewHas('logs',fn($logs)=>$logs->total()===0);
        $this->get(route('products.logs',['company'=>'Wire']))->assertOk()->assertViewHas('logs',fn($logs)=>$logs->total()===2);
        $this->get(route('products.logs',['company'=>'%']))->assertViewHas('logs',fn($logs)=>$logs->total()===0);
    }
    public function test_text_fields_can_be_blank_and_text_is_escaped(): void
    {
        $this->post(route('products.store'),$this->data(['name'=>'','item_code'=>'','company'=>'','purchase_price'=>'','retail_price'=>'']))->assertSessionHasNoErrors();
        $this->post(route('products.store'),$this->data(['purchase_price'=>'<script>alert(1)</script>','retail_price'=>null]))->assertSessionHasNoErrors();
        $this->get(route('products.logs'))->assertOk()->assertDontSee('<script>alert(1)</script>',false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false);
        $this->get(route('products.pricing'))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false);
    }
    public function test_logs_require_product_view_permission(): void
    {
        $role=Role::create(['name'=>'No prices','permissions'=>[]]);
        $user=User::create(['name'=>'Viewer','email'=>'view@example.test','password'=>'Testing1234','active'=>true,'role_id'=>$role->id]);
        $this->signIn($user);
        $this->get(route('products.logs'))->assertForbidden();
    }
}
