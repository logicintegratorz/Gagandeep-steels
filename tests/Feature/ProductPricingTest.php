<?php
namespace Tests\Feature;
use App\Models\{Product, Role, User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ProductPricingTest extends TestCase
{
    protected function setUp(): void { parent::setUp(); $this->signIn(); }
    private function data(array $overrides = []): array
    {
        return array_merge(['name'=>'Binding Wire','item_code'=>'WIRE-001','item_type'=>'Product','category'=>'Steel','unit'=>'Kg','tax_type'=>'GST 18%','active'=>'1','company'=>'Alpha Steel','effective_date'=>now()->toDateString(),'purchase_price'=>'100.125','retail_price'=>'125.500'], $overrides);
    }
    public function test_product_form_saves_and_pricing_is_separate_from_freight(): void
    {
        $this->get(route('products.create'))->assertOk()->assertSee('Item information');
        $this->post(route('products.store'), $this->data())->assertSessionHasNoErrors()->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['item_code'=>'WIRE-001','unit'=>'Kg']);
        $this->assertDatabaseHas('product_prices', ['company'=>'Alpha Steel','retail_price'=>'125.500']);
        $this->get(route('products.pricing'))->assertOk()->assertSee('Binding Wire')->assertSee('125.500')->assertSee('Alpha Steel');
        $this->assertDatabaseCount('freight_rates', 0);
        $this->get(route('home'))->assertSee(route('products.pricing'));
    }
    public function test_date_history_company_rates_and_purchase_fallback(): void
    {
        $yesterday = now()->subDay()->toDateString();
        $this->post(route('products.store'), $this->data(['effective_date'=>$yesterday,'retail_price'=>null]))->assertSessionHasNoErrors();
        $product = Product::firstOrFail();
        $this->put(route('products.update', $product), $this->data(['version'=>1,'retail_price'=>'150']))->assertSessionHasNoErrors();
        $this->put(route('products.update', $product), $this->data(['version'=>2,'company'=>'Beta Steel','retail_price'=>'160']))->assertSessionHasNoErrors();
        $this->get(route('products.pricing'))->assertOk()->assertSee('150')->assertSee('160')->assertSee('Beta Steel');
        $this->get(route('products.pricing', ['date'=>$yesterday]))->assertOk()->assertSee('100.125')->assertDontSee('150')->assertDontSee('Beta Steel');
        $this->get(route('products.pricing', ['date'=>now()->subDays(2)->toDateString()]))->assertSee('No prices available');
        $this->assertDatabaseCount('product_prices', 3);
    }
    public function test_same_date_update_is_unique_and_stale_edits_are_rejected(): void
    {
        $this->post(route('products.store'), $this->data());
        $product = Product::firstOrFail();
        $this->put(route('products.update', $product), $this->data(['version'=>1,'retail_price'=>'0']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('product_prices', 1);
        $this->get(route('products.pricing'))->assertSee('0');
        $this->put(route('products.update', $product), $this->data(['version'=>1,'retail_price'=>'200']))->assertSessionHasErrors('version');
        $this->assertDatabaseHas('product_prices', ['retail_price'=>'0']);
        $this->put(route('products.update', $product), $this->data(['version'=>2,'active'=>0]))->assertSessionHasNoErrors();
        $this->get(route('products.pricing'))->assertDontSee('Binding Wire');
        $this->get(route('products.index'))->assertSee('Binding Wire')->assertSee('Inactive');
    }
    public function test_edit_loads_selected_company_and_rejects_another_products_price(): void
    {
        $this->post(route('products.store'), $this->data());
        $product = Product::firstOrFail();
        $this->put(route('products.update', $product), $this->data(['version'=>1,'company'=>'Beta Steel','retail_price'=>'200']));
        $alpha = $product->prices()->where('company','Alpha Steel')->firstOrFail();
        $this->get(route('products.edit', ['product'=>$product, 'price'=>$alpha->id]))->assertOk()
            ->assertViewHas('price', fn ($price) => $price->company === 'Alpha Steel' && $price->retail_price === '125.500');
        $this->post(route('products.store'), $this->data(['item_code'=>'SECOND','name'=>'Second product']));
        $other = Product::where('item_code','SECOND')->firstOrFail()->prices()->firstOrFail();
        $this->get(route('products.edit', ['product'=>$product, 'price'=>$other->id]))->assertNotFound();
    }
    public function test_free_text_and_duplicate_item_ids_are_allowed_but_dates_and_uploads_stay_valid(): void
    {
        $this->post(route('products.store'), $this->data(['purchase_price'=>'500 + freight','retail_price'=>'On request']))->assertSessionHasNoErrors();
        $this->post(route('products.store'), $this->data(['purchase_price'=>'Any text']))->assertSessionHasNoErrors();
        $this->get(route('products.pricing'))->assertSee('On request');
        $this->post(route('products.store'), $this->data(['effective_date'=>now()->addDay()->toDateString()]))->assertSessionHasErrors('effective_date');
        $this->post(route('products.store'), $this->data(['picture'=>UploadedFile::fake()->create('bad.svg', 10, 'image/svg+xml')]))->assertSessionHasErrors('picture');
        $this->get(route('products.pricing', ['date'=>'not-a-date']))->assertSessionHasErrors('date');
        $this->assertDatabaseCount('products', 2);
    }
    public function test_permissions_protect_product_writes_and_navigation(): void
    {
        $role=Role::create(['name'=>'Price reader','permissions'=>['products.view']]);
        $user=User::create(['name'=>'Reader','email'=>'price-reader@example.test','password'=>'Testing1234','active'=>true,'role_id'=>$role->id]);
        $this->signIn($user);
        $this->get(route('products.pricing'))->assertOk()->assertDontSee('Add product');
        $this->post(route('products.store'), $this->data())->assertForbidden();
        $this->get(route('products.create'))->assertForbidden();
        $role->update(['permissions'=>[]]); $user->unsetRelation('role');
        $this->get(route('products.pricing'))->assertForbidden();
        $this->get(route('home'))->assertDontSee(route('products.pricing'));
    }
    public function test_product_picture_is_validated_stored_privately_and_served_to_authorized_users(): void
    {
        Storage::fake('local');
        $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6r4sAAAAASUVORK5CYII=');
        $file=UploadedFile::fake()->createWithContent('product.png', $png);
        $this->post(route('products.store'), $this->data(['picture'=>$file]))->assertSessionHasNoErrors();
        $product=Product::firstOrFail();
        Storage::disk('local')->assertExists($product->picture_path);
        $this->get(route('products.picture', $product))->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
    }
}
