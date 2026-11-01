<?php
namespace Tests\Feature;
use App\Models\{Product,ProductPriceLog,Role,User};
use Tests\TestCase;
final class CompanyPriceTest extends TestCase {
 public function test_save_company_rows_and_keep_history(): void {
  $this->signIn();
  $p=Product::create(['name'=>'Angle','item_code'=>'A','unit'=>'Kg','item_type'=>'Product','active'=>true]);
  $p->prices()->create(['company'=>'Old company','effective_date'=>now()->subDay()->toDateString(),'purchase_price'=>'20000']);
  $response=$this->post(route('products.company-prices',$p),['selected_row'=>1,'version'=>$p->fresh()->version,'rows'=>[['company'=>'New company','price'=>'22000'],['company'=>'Another','price'=>'On request']]]);
  $response->assertSessionHasNoErrors()->assertRedirect(route('products.pricing'));
  $chosen=$p->prices()->where('company','Another')->first();
  $response->assertSessionHas('size_price',$chosen->id);
  $this->assertDatabaseHas('product_prices',['product_id'=>$p->id,'company'=>'New company','retail_price'=>'22000','removed'=>false]);
  $this->assertDatabaseHas('product_prices',['product_id'=>$p->id,'company'=>'Old company','removed'=>true]);
  $this->assertDatabaseHas('product_price_logs',['product_id'=>$p->id,'company'=>'Old company','event'=>'Removed','old_purchase_price'=>'20000']);
  $this->get(route('products.pricing'))->assertOk()->assertSee('New company')->assertDontSee('Old company')->assertDontSee('<h2>Edit company prices</h2>', false)->assertSee('data-rate-trigger',false);
  $this->get(route('products.pricing',['date'=>now()->subDay()->toDateString()]))->assertOk()->assertSee('Old company');
  $this->post(route('products.company-prices',$p),['version'=>1,'rows'=>[['company'=>'Oops','price'=>'1']]])->assertSessionHasErrors('version');
 }
 public function test_viewer_cannot_change_company_prices(): void {
  $p=Product::create(['name'=>'Angle','item_code'=>'A','unit'=>'Kg','item_type'=>'Product','active'=>true]);
  $role=Role::create(['name'=>'Reader','permissions'=>['products.view']]);
  $u=User::create(['name'=>'Reader','email'=>'reader@example.test','password'=>'TestPassword1234','active'=>true,'role_id'=>$role->id]);$this->signIn($u);
  $this->post(route('products.company-prices',$p),['version'=>1,'rows'=>[['company'=>'X','price'=>'1']]])->assertForbidden();
 }
}
