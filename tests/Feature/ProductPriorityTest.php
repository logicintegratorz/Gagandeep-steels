<?php
namespace Tests\Feature;
use App\Models\Product;
use Tests\TestCase;
final class ProductPriorityTest extends TestCase {
 public function test_slot_can_be_cleared_and_replaced_without_deleting_product(): void {
  $this->signIn();$ids=[];
  foreach(['Angle','Pipe'] as $name){$p=Product::create(['name'=>$name,'item_code'=>$name,'unit'=>'Kg','item_type'=>'Product','active'=>true]);$p->prices()->create(['company'=>'X','effective_date'=>now()->toDateString(),'purchase_price'=>'100']);$ids[]=$p->id;}
  $this->get(route('products.index',['slot'=>0]))->assertOk()->assertViewHas('directorySelected',[$ids[0]]);
  $this->post(route('products.order'),['slot'=>0])->assertRedirect(route('products.pricing'));
  $this->assertSame([null,$ids[1]],auth()->user()->fresh()->product_slots);
  $this->get(route('products.pricing'))->assertOk()->assertViewHas('pricingSlots',fn($slots)=>$slots[0]===null && $slots[1]->id===$ids[1]);
  $this->post(route('products.order'),['slot'=>0,'selected'=>[$ids[1]]])->assertRedirect();
  $this->assertSame([$ids[1],null],auth()->user()->fresh()->product_slots);
  $this->assertDatabaseHas('products',['id'=>$ids[0]]);
 }
 public function test_selected_products_appear_first_without_hiding_others(): void {
  $this->signIn(); $ids=[];
  foreach (['Angle','Channel','TMT Bar'] as $name) {
   $p=Product::create(['name'=>$name,'item_code'=>$name,'unit'=>'Kg','item_type'=>'Product','active'=>true]);
   $p->prices()->create(['company'=>'Steel','effective_date'=>now()->toDateString(),'purchase_price'=>'100']);$ids[]=$p->id;
  }
  $this->get(route('products.index'))->assertOk()->assertSee('selected-product-rates')->assertSee('selected[]',false);
  $this->post(route('products.order'),['selected'=>[$ids[2],$ids[0]]])->assertRedirect(route('products.pricing'));
  $this->assertSame([$ids[2],$ids[0]],auth()->user()->fresh()->product_order);
  $this->get(route('products.pricing'))->assertOk()->assertViewHas('products',fn($items)=>$items->pluck('id')->all()===[$ids[2],$ids[0],$ids[1]]);
  $user=auth()->user()->fresh();
  $this->post(route('logout'));
  $this->signIn($user);
  $this->get(route('products.pricing'))->assertOk()->assertViewHas('products',fn($items)=>$items->pluck('id')->all()===[$ids[2],$ids[0],$ids[1]]);
  $this->post(route('products.order'),['selected'=>[$ids[2],$ids[1]]])->assertRedirect(route('products.pricing'));
  $this->assertSame([$ids[2],null,$ids[1]],auth()->user()->fresh()->product_slots);
  $this->get(route('products.pricing'))->assertOk()->assertViewHas('pricingSlots',fn($slots)=>$slots[0]->id===$ids[2] && $slots[1]===null && $slots[2]->id===$ids[1]);
  $this->post(route('products.order'),[])->assertRedirect(route('products.pricing'));
  $this->assertSame([null,null,null],auth()->user()->fresh()->product_slots);
  $this->get(route('products.pricing'))->assertOk()->assertViewHas('products',fn($items)=>$items->pluck('id')->all()===$ids);
 }
}
