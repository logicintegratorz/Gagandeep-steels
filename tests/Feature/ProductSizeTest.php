<?php
namespace Tests\Feature;
use App\Models\{Product, ProductSize, Role, User};
use Tests\TestCase;
final class ProductSizeTest extends TestCase
{
    public function test_directory_uses_product_header_and_company_popup_rate(): void {
        $this->signIn(); $p=$this->product('Product header');
        $price=$p->prices()->create(['company'=>'Warehouse company','effective_date'=>now()->toDateString(),'retail_price'=>'45000']);
        $this->post(route('products.sizes.store',$p),['price'=>$price->id,'size'=>'25x5','manufacturer_price'=>'40000','godown_price'=>'old value'])->assertSessionHasNoErrors();
        $this->get(route('sizes.index'))->assertOk()->assertSee('Manufacturer/Seller')->assertSee('45000')->assertDontSee('old value')->assertViewHas('groups',fn($g)=>$g->get($p->id)->first()['godown']==='45000');
        $this->get(route('products.pricing'))->assertOk()->assertSee('data-summary-rate-trigger',false)->assertSee('data-related-company',false)->assertSee('data-picked-basic',false)->assertSee('data-size-company-rows',false);
    }
    public function test_company_sizes_are_independent(): void {
        $this->signIn(); $p=$this->product('Scoped');
        $a=$p->prices()->create(['company'=>'Alpha','effective_date'=>now()->toDateString(),'retail_price'=>'100']);
        $b=$p->prices()->create(['company'=>'Beta','effective_date'=>now()->toDateString(),'retail_price'=>'200']);
        $this->post(route('products.sizes.store',$p),['price'=>$a->id,'size'=>'25x5','manufacturer_price'=>'10'])->assertSessionHasNoErrors();
        $this->post(route('products.sizes.store',$p),['price'=>$b->id,'size'=>'25x5','manufacturer_price'=>'20'])->assertSessionHasNoErrors();
        $sa=ProductSize::where('company','Alpha')->firstOrFail();
        $sb=ProductSize::where('company','Beta')->firstOrFail();
        $this->put(route('products.sizes.update',[$p,$sa]),['price'=>$b->id,'manufacturer_price'=>'99'])->assertNotFound();
        $this->put(route('products.sizes.update',[$p,$sa]),['return_to'=>'all-sizes','manufacturer_price'=>'30'])->assertRedirect(route('sizes.index'));
        $this->assertSame('20',$sb->fresh()->manufacturer_price);
        $this->delete(route('products.sizes.destroy',[$p,$sa]),['return_to'=>'all-sizes'])->assertRedirect(route('sizes.index'));
        $this->assertDatabaseHas('product_sizes',['id'=>$sb->id,'company'=>'Beta']);
        $this->get(route('sizes.index'))->assertOk()->assertViewHas('groups',fn($g)=>$g->get($p->id)->count()===1 && $g->get($p->id)->first()['company']==='Beta');
        $this->get(route('products.pricing'))->assertOk()->assertSee('data-company="Beta"',false);
        $this->post(route('products.sizes.store',$p),['return_to'=>'all-sizes','company'=>'Gamma','size'=>'30x6'])->assertRedirect(route('sizes.index'));
        $this->assertDatabaseHas('product_sizes',['company'=>'Gamma','size'=>'30x6']);
    }
    public function test_all_sizes_edit_delete_return_to_directory(): void {
        $this->signIn(); $p=$this->product('Directory');
        $this->get(route('sizes.index'))->assertOk()->assertSee('all-size-add')->assertSee('data-add-size-product',false);
        $this->post(route('products.sizes.store',$p),['return_to'=>'all-sizes','company'=>'','size'=>'30x5','manufacturer_price'=>'250','godown_price'=>'300'])->assertRedirect(route('sizes.index'));
        $this->assertDatabaseHas('product_sizes',['product_id'=>$p->id,'size'=>'30x5','manufacturer_price'=>'250','godown_price'=>'300']);
        $size=new ProductSize(['size'=>'25x5','manufacturer_price'=>'100']);
        $size->product_id=$p->id; $size->save();
        $this->get(route('sizes.index'))->assertOk()->assertSee('data-size-add-open',false)->assertSee('Delete');
        $this->put(route('products.sizes.update',[$p,$size]),['return_to'=>'all-sizes','manufacturer_price'=>'120','godown_price'=>'140'])->assertRedirect(route('sizes.index'));
        $this->assertSame('120',$size->fresh()->manufacturer_price);
        $this->delete(route('products.sizes.destroy',[$p,$size]),['return_to'=>'all-sizes'])->assertRedirect(route('sizes.index'));
        $this->assertDatabaseMissing('product_sizes',['id'=>$size->id]);
        $this->assertDatabaseHas('crm_notifications',['title'=>'Size updated']);
        $this->assertDatabaseHas('crm_notifications',['title'=>'Size deleted']);
    }
    public function test_location_prices_save_update_and_directory(): void {
        $this->signIn(); $p=$this->product('Location prices');
        $this->post(route('products.sizes.store',$p),['size'=>'25 x 5','manufacturer_price'=>'500 + freight','godown_price'=>'600'])->assertSessionHasNoErrors();
        $size=ProductSize::where('product_id',$p->id)->firstOrFail();
        $this->assertSame('500 + freight',$size->manufacturer_price);
        $this->put(route('products.sizes.update',[$p,$size]),['manufacturer_price'=>'550','godown_price'=>'On request'])->assertSessionHasNoErrors();
        $this->assertSame('On request',$size->fresh()->godown_price);
        $this->get(route('sizes.index'))->assertOk()->assertSee('Location prices')->assertSee('550')->assertSee('50000')->assertDontSee('On request')->assertSee('data-all-size-filters',false);
        $this->get(route('products.pricing'))->assertOk()->assertSee('data-size-price-tab',false)->assertSee('Manufacturer/Seller')->assertSee('Godown Price');
        $this->assertDatabaseHas('product_size_activities',['size_id'=>$size->id,'activity'=>'Edited']);
    }
    public function test_inline_size_save_returns_to_rates_with_selected_product(): void {
        $this->signIn(); $p=$this->product('Inline'); $price=$p->prices()->first();
        $response=$this->post(route('products.sizes.store',$p),['return_to'=>'rates','price'=>$price->id,'size'=>'40 x 5','base_price'=>'52000','difference'=>'1500']);
        $response->assertRedirect(route('products.pricing',['date'=>now('Asia/Kolkata')->toDateString()]))->assertSessionHas('size_product',$p->id)->assertSessionHas('size_price',$price->id);
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('product-summary-heading',false)->assertSee('52000')->assertSee('data-size-picker-search',false)->assertSee('Size List')->assertSee('size-add-dialog')->assertDontSee('data-size-select',false)->assertSee('40 x 5')->assertSee('data-reopen="'.$price->id.'"',false);
    }
    public function test_size_update_delete_and_product_scope(): void {
        $this->signIn(); $p=$this->product('Edit'); $other=$this->product('Other');
        $size=(new ProductSize(['size'=>'25 x 5','base_price'=>'50000']))->forceFill(['product_id'=>$p->id,'company'=>'Alpha Steel']); $size->save();
        $this->put(route('products.sizes.update',[$other,$size]),['size'=>'Wrong'])->assertNotFound();
        $this->delete(route('products.sizes.destroy',[$other,$size]))->assertNotFound();
        $this->put(route('products.sizes.update',[$p,$size]),['size'=>'25 x 6','base_price'=>'52000','difference'=>'1500','notes'=>'Updated','return_to'=>'rates'])->assertSessionHas('success','Product size updated.');
        $this->assertDatabaseHas('product_sizes',['id'=>$size->id,'base_price'=>'52000','size'=>'25 x 6']);
        $this->delete(route('products.sizes.destroy',[$p,$size]),['return_to'=>'rates'])->assertSessionHas('success','Product size deleted.');
        $this->assertDatabaseMissing('product_sizes',['id'=>$size->id]);
        $this->assertDatabaseHas('product_size_activities',['size_id'=>$size->id,'activity'=>'Edited','size_name'=>'25 x 6']);
        $this->assertDatabaseHas('product_size_activities',['size_id'=>$size->id,'activity'=>'Deleted']);
        $logs=\App\Models\ProductSizeActivity::where('size_id',$size->id)->orderBy('id')->get();
        $this->assertCount(2,$logs);
        $this->assertSame('50000',$logs[0]->before_values['base_price']);
        $this->assertSame('52000',$logs[0]->after_values['base_price']);
        $this->assertNotNull($logs[1]->occurred_at);
        $this->get(route('products.pricing'))->assertOk()->assertSee('Last Update')->assertSee('size-last-activity')->assertDontSee('Size activity history');
    }
    private function product(string $code): Product {
        $p=Product::create(['name'=>'Angle '.$code,'item_code'=>$code,'unit'=>'Kg','item_type'=>'Product','active'=>true]);
        $p->prices()->create(['company'=>'Alpha Steel','effective_date'=>now()->toDateString(),'purchase_price'=>'50000']);
        return $p;
    }
    public function test_add_size_returns_to_product_and_preserves_text(): void {
        $this->signIn(); $p=$this->product('A'); $price=$p->prices()->first();
        $response=$this->post(route('products.sizes.store',$p),['price'=>$price->id,'date'=>now()->toDateString(),'size'=>'25 x 5','difference'=>'2000','weight'=>'2.5 kg','cc'=>'Custom CC','notes'=>'Saved note']);
        $response->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('product_sizes',['product_id'=>$p->id,'size'=>'25 x 5','weight'=>'2.5 kg']);
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('25 x 5')->assertSee('Saved note')->assertSee('50000');
        $this->get(route('products.sizes',$p))->assertOk()->assertSee('data-size-select',false)->assertDontSee('Add size')->assertDontSee('Size directory');
        $this->post(route('products.sizes.store',$p),['size'=>'Any size','base_price'=>'Ask supplier','difference'=>'On request'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_sizes',['base_price'=>'Ask supplier','difference'=>'On request']);
    }
    public function test_sizes_and_price_context_are_scoped_to_product(): void {
        $this->signIn(); $a=$this->product('A'); $b=$this->product('B');
        (new ProductSize(['size'=>'Secret A']))->forceFill(['product_id'=>$a->id])->save();
        $this->get(route('products.sizes',$b))->assertOk()->assertDontSee('Secret A');
        $this->get(route('products.sizes',['product'=>$a,'price'=>$b->prices()->first()->id]))->assertNotFound();
        $this->post(route('products.sizes.store',$a),['price'=>$b->prices()->first()->id,'size'=>'Wrong'])->assertNotFound();
        $this->assertDatabaseMissing('product_sizes',['size'=>'Wrong']);
    }
    public function test_viewer_can_view_but_cannot_add_sizes(): void {
        $p=$this->product('A'); $role=Role::create(['name'=>'Reader','permissions'=>['products.view']]);
        $u=User::create(['name'=>'Reader','email'=>'reader@example.test','password'=>'TestPassword1234','active'=>true,'role_id'=>$role->id]); $this->signIn($u);
        $this->get(route('products.sizes',$p))->assertOk()->assertDontSee('Add size');
        $this->post(route('products.sizes.store',$p),['size'=>'25 x 5'])->assertForbidden();
        $size=(new ProductSize(['size'=>'Protected']))->forceFill(['product_id'=>$p->id]); $size->save();
        $this->put(route('products.sizes.update',[$p,$size]),['size'=>'Changed'])->assertForbidden();
        $this->delete(route('products.sizes.destroy',[$p,$size]))->assertForbidden();

    }
}

