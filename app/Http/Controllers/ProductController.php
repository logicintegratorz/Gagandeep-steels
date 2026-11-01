<?php
namespace App\Http\Controllers;

use App\Models\{Product, ProductPriceLog};
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\{Rule, ValidationException};
use Illuminate\Database\UniqueConstraintViolationException;

final class ProductController
{
    public function pricing(Request $request)
    {
        $data = $request->validate(['selected'=>['nullable','array','max:100'], 'selected.*'=>['integer','min:1','distinct'], 'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:today']]);
        $date = $data['date'] ?? now('Asia/Kolkata')->toDateString();
        $products = Product::where('active', true)->whereHas('prices', fn ($q) => $q->whereDate('effective_date', '<=', $date))
            ->with(['prices' => fn ($q) => $q->whereDate('effective_date', '<=', $date)->orderByDesc('effective_date')->orderByDesc('id')])
            ->orderBy('name')->get();
        $products = $products->filter(function ($product) {
            $product->setRelation('prices', $product->prices->unique('company')->reject(fn($price) => $price->removed)->values());
            return $product->prices->isNotEmpty();
        });
        $selected = $request->user()->product_order ?? [];
        $priority = array_flip($selected);
        $products = $products->sortBy(fn($product) => $priority[$product->id] ?? count($selected))->values();
        $slots = $request->user()->product_slots ?? $products->pluck('id')->all();
        $pricingSlots = collect($slots)->map(fn($id) => $products->firstWhere('id',$id));
        // Keep one trailing chooser without moving any occupied or internal empty position.
        while ($pricingSlots->count() > 1 && $pricingSlots->last() === null && $pricingSlots->get($pricingSlots->count() - 2) === null) {
            $pricingSlots->pop();
        }
        if ($pricingSlots->isEmpty() || $pricingSlots->last() !== null) $pricingSlots->push(null);
        return view('products.pricing', ['pricingSlots'=>$pricingSlots, 'selected'=>$selected, 'products' => $products, 'sizeActivities' => \App\Models\ProductSizeActivity::whereIn('product_id', $products->pluck('id'))->orderByDesc('id')->get()->groupBy('product_id'), 'productSizes' => \App\Models\ProductSize::whereIn('product_id', $products->pluck('id'))->orderBy('size')->get()->groupBy('product_id'), 'date' => $date, 'previousDate' => CarbonImmutable::parse($date)->subDay()->toDateString()]);
    }
    private function defaultSlots(Request $request): array {
        $products=Product::where('active',true)->with(['prices'=>fn($q)=>$q->whereDate('effective_date','<=',now('Asia/Kolkata')->toDateString())->orderByDesc('effective_date')->orderByDesc('id')])->orderBy('name')->get()->filter(fn($p)=>$p->prices->unique('company')->reject(fn($v)=>$v->removed)->isNotEmpty());
        $priority=array_flip($request->user()->product_order ?? []);
        return $products->sortBy(fn($p)=>$priority[$p->id] ?? count($priority))->pluck('id')->all();
    }
    public function saveOrder(Request $request) {
        $data=$request->validate(['slot'=>['nullable','integer','min:0','max:999'],'selected'=>['nullable','array','max:1000'],'selected.*'=>['integer','min:1','distinct','exists:products,id']]);
        $selected=array_map('intval',$data['selected'] ?? []);
        if (isset($data['slot'])) {
            if(count($selected)>1) throw ValidationException::withMessages(['selected'=>'Choose one product for this position.']);
            $slots=$request->user()->product_slots ?? $this->defaultSlots($request);
            $index=(int)$data['slot'];
            abort_if($index>count($slots),422);
            $chosen=$selected[0] ?? null;
            if($chosen) {
                $available=Product::whereKey($chosen)->where('active',true)->exists();
                if(!$available) throw ValidationException::withMessages(['selected'=>'Choose an active product.']);
                foreach($slots as $key=>$id) if($id===$chosen) $slots[$key]=null;
            }
            $slots[$index]=$chosen;
            $request->user()->forceFill(['product_slots'=>array_values($slots)])->save();
        } else {
            $slots=$request->user()->product_slots;
            if ($slots !== null) {
                // Clearing a selection leaves a gap; never compact existing positions.
                $slots=array_map(fn($id)=>in_array($id,$selected,true) ? $id : null,$slots);
                foreach($selected as $id) {
                    if(in_array($id,$slots,true)) continue;
                    $empty=array_search(null,$slots,true);
                    if($empty === false) $slots[]=$id; else $slots[$empty]=$id;
                }
            } else {
                $priority=array_flip($selected);
                $slots=collect($this->defaultSlots($request))->sortBy(fn($id)=>$priority[$id] ?? count($priority))->values()->all();
            }
            $request->user()->forceFill(['product_order'=>$selected,'product_slots'=>$slots])->save();
        }
        return redirect()->route('products.pricing');
    }
    public function index(Request $request)
    {
        $request->validate(['slot'=>['nullable','integer','min:0','max:999']]);
        $slot=$request->filled('slot') ? $request->integer('slot') : null;
        $slots=$request->user()->product_slots ?? $this->defaultSlots($request);
        $chosen=$slot !== null ? ($slots[$slot] ?? null) : null;
        $query=Product::query();
        if($chosen) $query->orderByRaw('id = ? DESC',[$chosen]);
        return view('products.index', ['slot'=>$slot,'directorySelected'=>$slot !== null ? array_filter([$chosen]) : array_values(array_filter($request->user()->product_slots ?? ($request->user()->product_order ?? []))), 'products' => $query->orderBy('name')->paginate(20)->withQueryString()]);
    }
    public function logs(Request $request)
    {
        $filters = $request->validate([
            'company'=>['nullable','string','max:150'],
            'date'=>['nullable','date_format:Y-m-d'],
            'from'=>['nullable','date_format:Y-m-d'],
            'to'=>['nullable','date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'date_basis'=>['nullable', Rule::in(['changed_at','effective_date'])],
        ]);
        $query = ProductPriceLog::query();
        if (filled($filters['company'] ?? null)) {
            $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($filters['company'])).'%';
            $query->where(fn ($match) => $match->whereRaw("company LIKE ? ESCAPE '!'", [$term])->orWhereRaw("product_name LIKE ? ESCAPE '!'", [$term]));
        }
        if (filled($filters['date'] ?? null)) $query->whereDate('changed_at', $filters['date']);
        $basis = $filters['date_basis'] ?? 'changed_at';
        if (filled($filters['from'] ?? null)) $query->whereDate($basis, '>=', $filters['from']);
        if (filled($filters['to'] ?? null)) $query->whereDate($basis, '<=', $filters['to']);
        return view('products.logs', ['logs'=>$query->orderBy('product_name')->orderBy('product_id')->orderBy('company')->orderByDesc('changed_at')->orderByDesc('id')->paginate(50)->withQueryString(), 'filters'=>$filters]);
    }
    public function create() { return view('products.form', ['product' => null, 'price' => null]); }
    public function edit(Request $request, Product $product)
    {
        $request->validate(['price' => ['nullable', 'integer', 'min:1']]);
        $price = $request->filled('price') ? $product->prices()->findOrFail($request->integer('price')) : $product->prices()->orderByDesc('effective_date')->orderByDesc('id')->first();
        return view('products.form', ['product' => $product, 'price' => $price, 'activities' => \App\Models\ProductSizeActivity::where('product_id', $product->id)->orderByDesc('id')->get(), 'sizes' => \App\Models\ProductSize::where('product_id', $product->id)->where('company', $price?->company ?? '')->orderBy('size')->get()]);
    }
    public function store(Request $request) { return $this->save($request); }
    public function update(Request $request, Product $product) { return $this->save($request, $product); }
    private function save(Request $request, ?Product $product = null)
    {
        foreach (['name', 'item_code', 'company', 'category', 'unit', 'tax_type', 'hsn_sac'] as $field) {
            if (is_string($request->input($field))) $request->merge([$field => trim($request->input($field))]);
        }
        $data = $request->validate([
            'name' => ['nullable','string'],
            'item_code' => ['nullable','string'],
            'hsn_sac' => ['nullable','string'],
            'item_type' => ['required', Rule::in(['Product', 'Service'])],
            'category' => ['nullable','string'],
            'unit' => ['required', Rule::in(['Each', 'Kg', 'Tonne', 'Quintal', 'Metre', 'Bundle', 'Piece', 'Litre', 'Hour'])],
            'tax_type' => ['nullable','string'],
            'description' => ['nullable','string'],
            'active' => ['required','boolean'],
            'company' => ['nullable','string'],
            'effective_date' => ['required','date_format:Y-m-d','after_or_equal:2000-01-01','before_or_equal:today'],
            'purchase_price' => ['nullable','string'],
            'retail_price' => ['nullable','string'],
            'picture' => ['nullable','file','image','mimes:jpg,jpeg,png,gif','max:2048','dimensions:max_width=6000,max_height=6000'],
            'version' => [$product ? 'required' : 'nullable','integer','min:1'],
        ]);
        $data['company'] = $data['company'] ?? '';
        $data['purchase_price'] = $data['purchase_price'] ?? null;
        $path = null;
        $oldPath = null;
        try {
            if ($request->hasFile('picture')) {
                $path = $request->file('picture')->store('product-pictures', 'local');
                if (!$path) throw ValidationException::withMessages(['picture' => 'The picture could not be saved. Please try again.']);
            }
            DB::transaction(function () use ($request, $product, $data, $path, &$oldPath) {
                $current = $product ? Product::whereKey($product->id)->lockForUpdate()->firstOrFail() : new Product;
                if ($product && $current->version !== (int) $data['version']) throw ValidationException::withMessages(['version' => 'This product changed elsewhere. Reopen it before saving.']);
                $current->fill(collect($data)->except(['company','effective_date','purchase_price','retail_price','picture','version'])->all());
                if ($path) { $oldPath = $current->picture_path; $current->picture_path = $path; }
                if ($product) $current->version++;
                $current->save();
                $price = $current->prices()->firstOrNew(['company' => $data['company'], 'effective_date' => $data['effective_date']]);
                $existing = $price->exists;
                $previous = $existing ? clone $price : $current->prices()->where('company', $data['company'])->whereDate('effective_date', '<=', $data['effective_date'])->orderByDesc('effective_date')->first();
                $price->removed = false;
                $price->fill(['purchase_price' => $data['purchase_price'], 'retail_price' => $data['retail_price'] ?? null]);
                if (!$existing || $price->isDirty(['purchase_price', 'retail_price'])) {
                    ProductPriceLog::create([
                        'product_id'=>$current->id, 'product_name'=>$current->name, 'item_code'=>$current->item_code, 'unit'=>$current->unit,
                        'company'=>$data['company'], 'effective_date'=>$data['effective_date'],
                        'old_purchase_price'=>$previous?->purchase_price, 'old_retail_price'=>$previous?->retail_price,
                        'purchase_price'=>$price->purchase_price, 'retail_price'=>$price->retail_price,
                        'event'=>$existing ? 'Changed' : 'Added', 'changed_by'=>$request->user()->name, 'changed_at'=>now(),
                    ]);
                }
                $price->save();
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            if ($error instanceof UniqueConstraintViolationException) throw ValidationException::withMessages(['item_code' => 'This item ID already exists.']);
            throw $error;
        }
        if ($oldPath) Storage::disk('local')->delete($oldPath);
        return redirect()->route('products.index')->with('success', $product ? 'Product and dated price updated.' : 'Product added. Its price is available on the Rates page.');
    }
    public function picture(Product $product)
    {
        abort_unless($product->picture_path && Storage::disk('local')->exists($product->picture_path), 404);
        return response()->file(Storage::disk('local')->path($product->picture_path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
