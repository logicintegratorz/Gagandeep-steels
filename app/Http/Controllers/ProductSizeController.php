<?php
namespace App\Http\Controllers;
use App\Models\{Product, ProductSize, ProductSizeActivity};
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
final class ProductSizeController
{
    public function index(Request $request)
    {
        $products = Product::with(['sizes', 'prices' => fn ($query) => $query->orderByDesc('effective_date')->orderByDesc('id')])->orderBy('name')->get();
        $groups = collect();
        foreach ($products as $product) {
            foreach ($product->sizes as $size) {
                $rate = $product->prices->first(fn ($price) => ($price->company ?? '') === ($size->company ?? '') && $price->effective_date->toDateString() <= now('Asia/Kolkata')->toDateString());
                $groups->push(['company'=>$size->company ?: 'Unspecified company', 'product'=>$product, 'size'=>$size, 'godown'=>($rate && !$rate->removed) ? ($rate->retail_price ?? $rate->purchase_price) : null]);
            }
        }
        return view('products.all-sizes', ['groups'=>$groups->groupBy(fn ($entry) => $entry['product']->id), 'products'=>$products]);
    }
    private function context(Request $request, Product $product): array
    {
        $data=$request->validate(['price'=>['nullable','integer','min:1'],'date'=>['nullable','date_format:Y-m-d','before_or_equal:today'],'selected'=>['nullable','integer','min:1']]);
        $date=$data['date'] ?? now('Asia/Kolkata')->toDateString();
        $query=$product->prices()->whereDate('effective_date','<=',$date);
        $price=isset($data['price']) ? $query->findOrFail($data['price']) : $query->orderByDesc('effective_date')->orderByDesc('id')->firstOrFail();
        return [$price,$date];
    }
    public function show(Request $request, Product $product)
    {
        [$price,$date]=$this->context($request,$product);
        $sizes=ProductSize::where('product_id',$product->id)->where('company',$price->company ?? '')->orderBy('size')->orderBy('id')->get();
        return view('products.sizes',compact('product','price','date','sizes'));
    }
    public function store(Request $request, Product $product)
    {
        [$price,$date]=$request->input('return_to') === 'all-sizes' ? [null, now('Asia/Kolkata')->toDateString()] : $this->context($request,$product);
        $data=$request->validate(array_fill_keys(['size','base_price','difference','weight','cc','notes','manufacturer_price','godown_price'],['nullable','string']));
        $company = $price ? ($price->company ?? '') : ($request->validate(['company'=>['present','nullable','string']])['company'] ?? '');
        $size=new ProductSize($data); $size->company=$company; $size->product_id=$product->id; $size->save();
        return $this->saved($request,$product,$price,$date,$size->id,'Product size added.');
    }
    private function saved($request,$product,$price,$date,$id,$message) {
        if ($request->input('return_to') === 'all-sizes') return redirect()->route('sizes.index')->with('success',$message);
        if ($request->input('return_to') === 'rates') {
            return redirect()->route('products.pricing', ['date'=>$date])
                ->with(['success'=>$message, 'size_product'=>$product->id, 'size_price'=>$price->id, 'size_id'=>$id]);
        }
        return redirect()->route('products.edit',['product'=>$product,'price'=>$price->id])->with('success',$message);
    }
    public function update(Request $request, Product $product, ProductSize $size) {
        abort_unless($size->product_id === $product->id, 404);
        [$price,$date]=$request->input('return_to') === 'all-sizes' ? [null, now('Asia/Kolkata')->toDateString()] : $this->context($request,$product);
        if ($price) abort_unless(($size->company ?? '') === ($price->company ?? ''),404);
        $data=$request->validate(array_fill_keys(['size','base_price','difference','weight','cc','notes','manufacturer_price','godown_price'],['nullable','string']));
        DB::transaction(function () use ($request,$size,$data) {
            $current=ProductSize::whereKey($size->id)->lockForUpdate()->firstOrFail();
            $before=$current->only(['size','base_price','difference','weight','cc','notes','manufacturer_price','godown_price']);
            $current->fill($data);
            if ($current->isDirty()) {
                $current->save();
                $this->recordActivity($request,$current,'Edited',$before,$current->only(array_keys($before)));
            }
        });
        return $this->saved($request,$product,$price,$date,$size->id,'Product size updated.');
    }
    public function destroy(Request $request, Product $product, ProductSize $size) {
        abort_unless($size->product_id === $product->id, 404);
        [$price,$date]=$request->input('return_to') === 'all-sizes' ? [null, now('Asia/Kolkata')->toDateString()] : $this->context($request,$product);
        if ($price) abort_unless(($size->company ?? '') === ($price->company ?? ''),404);
        DB::transaction(function () use ($request,$size) {
            $current=ProductSize::whereKey($size->id)->lockForUpdate()->firstOrFail();
            $this->recordActivity($request,$current,'Deleted',$current->only(['size','base_price','difference','weight','cc','notes','manufacturer_price','godown_price']),null);
            $current->delete();
        });
        return $this->saved($request,$product,$price,$date,null,'Product size deleted.');
    }
    private function recordActivity($request,$size,$activity,$before,$after): void {
        ProductSizeActivity::create(['product_id'=>$size->product_id,'size_id'=>$size->id,
            'company'=>$size->company,'size_name'=>$size->size,'activity'=>$activity,'actor_name'=>$request->user()->name,
            'before_values'=>$before,'after_values'=>$after,'occurred_at'=>now()]);
    }
}
