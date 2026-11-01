<?php
namespace App\Http\Controllers;
use App\Models\{Product,ProductPriceLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class CompanyPriceController {
 public function save(Request $request, Product $product) {
  $data=$request->validate(['selected_row'=>'nullable|integer|min:0','version'=>'required|integer','rows'=>'required|array|min:1|max:100','rows.*.company'=>'nullable|string','rows.*.price'=>'nullable|string']);
  $rows=collect($data['rows'])->map(fn($r)=>['company'=>trim($r['company']??''),'price'=>$r['price']??null]);
  $chosenKey=$data['selected_row'] ?? $rows->keys()->first();
  if (!$rows->has($chosenKey)) throw ValidationException::withMessages(['selected_row'=>'Select an existing company row.']);
  if ($rows->pluck('company')->map(fn($v)=>mb_strtolower($v))->duplicates()->isNotEmpty()) throw ValidationException::withMessages(['rows'=>'Use one row per company.']);
  $selected=DB::transaction(function () use($request,$product,$data,$rows,$chosenKey) {
   $p=Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
   if ($p->version !== (int)$data['version']) throw ValidationException::withMessages(['version'=>'Prices changed elsewhere. Refresh the page before saving.']);
   $today=now('Asia/Kolkata')->toDateString();
   $current=$p->prices()->whereDate('effective_date','<=',$today)->orderByDesc('effective_date')->orderByDesc('id')->get()->unique('company')->reject(fn($v)=>$v->removed)->keyBy('company');
   $selected=null;
   foreach ($rows as $key=>$row) {
    $old=$current->get($row['company']);
    if ($old && \App\Support\PriceDisplay::format($old->retail_price ?? $old->purchase_price) === \App\Support\PriceDisplay::format($row['price'])) { if ((string)$key === (string)$chosenKey) $selected=$old->id; continue; }
    $price=$p->prices()->firstOrNew(['company'=>$row['company'],'effective_date'=>$today]);
    $price->purchase_price=$old?->purchase_price; $price->retail_price=$row['price'];
    if ($row['price'] === null) $price->purchase_price=null;
    $price->removed=false; $price->save();
    $this->log($request,$p,$price,$old,$old?'Changed':'Added');
    if ((string)$key === (string)$chosenKey) $selected=$price->id;
   }
   foreach ($current as $company=>$old) {
    if ($rows->contains(fn($row)=>$row['company'] === (string)$company)) continue;
    $price=$p->prices()->firstOrNew(['company'=>$old->company,'effective_date'=>$today]);
    $price->purchase_price=null; $price->retail_price=null; $price->removed=true; $price->save();
    $this->log($request,$p,$price,$old,'Removed');
   }
   $p->version++; $p->save();
   return $selected;
  });
  return redirect()->route('products.pricing')->with(['success'=>'Company prices updated.','size_product'=>$product->id,'size_price'=>$selected]);
 }
 private function log($request,$product,$price,$old,$event): void {
  ProductPriceLog::create(['product_id'=>$product->id,'product_name'=>$product->name,'item_code'=>$product->item_code,'unit'=>$product->unit,'company'=>$price->company,'effective_date'=>$price->effective_date,'old_purchase_price'=>$old?->purchase_price,'old_retail_price'=>$old?->retail_price,'purchase_price'=>$price->purchase_price,'retail_price'=>$price->retail_price,'event'=>$event,'changed_by'=>$request->user()->name,'changed_at'=>now()]);
 }
}
