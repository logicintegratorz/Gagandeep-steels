<x-app-layout title="All sizes">
<link rel="stylesheet" href="{{ asset('css/products.css').'?v='.filemtime(public_path('css/products.css')) }}">
<section class="panel">
<header class="pricing-heading"><h2>All sizes</h2>@if(auth()->user()->allows('products.edit'))<button type="button" class="button primary" data-size-add-open="all-size-add">Add size</button>@endif</header>
<div class="filters" data-all-size-filters>
<label>Company<input type="text" data-filter="company" placeholder="Search company"></label>
<label>Product<input type="text" data-filter="product" placeholder="Search product"></label>
<label>Size<input type="text" data-filter="size" placeholder="Search size"></label>
<button type="button" class="button secondary" data-clear-sizes>Clear</button>
</div>
@foreach($groups as $entries)
<section data-company-size-group>
<h3 class="company-log-heading">{{ $entries->first()['product']->name }}</h3>
<div class="table-scroll"><table class="size-directory all-sizes-table"><thead><tr><th>Company name</th><th>Size</th><th>Manufacturer/Seller</th><th>Basic Price</th><th>Basic difference</th><th>CC</th><th>Weight</th><th>Notes</th>@if(auth()->user()->allows('products.edit'))<th>Actions</th>@endif</tr></thead><tbody>
@foreach($entries as $entry)
@php($size = $entry['size'])
@php($editorId = 'all-size-edit-'.$loop->parent->index.'-'.$size->id)
<tr data-all-size-row data-company="{{ $entry['company'] }}" data-product="{{ $entry['product']->name }}" data-size="{{ $size->size }}">
<td>{{ $entry['company'] }}</td><td>{{ $size->size }}</td><td>{{ $size->manufacturer_price ?? '—' }}</td><td>{{ \App\Support\PriceDisplay::format($entry['godown'] ?? '—') }}</td><td>{{ $size->difference }}</td><td>{{ $size->cc }}</td><td>@include('products.weight-display', ['weightValue'=>$size->weight])</td><td>{{ $size->notes }}</td>
@if(auth()->user()->allows('products.edit'))
<td><div class="size-row-actions"><button type="button" class="button secondary" data-size-add-open="{{ $editorId }}">Edit</button>
<form method="POST" action="{{ route('products.sizes.destroy', [$entry['product'],$size]) }}" data-delete-form data-description="Delete size {{ $size->size }} for {{ $entry['product']->name }}? Company: {{ $size->company ?: 'Unspecified company' }}.">
@csrf @method('DELETE')<input type="hidden" name="return_to" value="all-sizes"><button type="submit" class="button danger" data-delete-trigger disabled>Delete</button>
</form></div></td>
@endif
</tr>
@endforeach
</tbody></table></div>
@if(auth()->user()->allows('products.edit'))
@foreach($entries as $entry)
@php($size = $entry['size'])
<dialog class="size-add-dialog" id="all-size-edit-{{ $loop->parent->index }}-{{ $size->id }}">
<header class="pricing-heading"><h2>Edit size · {{ $entry['product']->name }} · {{ $size->company ?: 'Unspecified company' }}</h2><button type="button" class="button secondary" data-size-add-close>Cancel</button></header>
<form method="POST" action="{{ route('products.sizes.update', [$entry['product'],$size]) }}" class="size-add-form" data-save-form>
@csrf @method('PUT')<input type="hidden" name="return_to" value="all-sizes">
@foreach(['size'=>'Size','manufacturer_price'=>'Manufacturer/Seller','difference'=>'Basic difference','cc'=>'CC','notes'=>'Notes'] as $field=>$label)
<label>{{ $label }}<input type="text" name="{{ $field }}" value="{{ $size->$field }}"></label>
@endforeach
@include('products.weight-input', ['weightValue'=>$size->weight])
<div class="size-edit-actions"><button class="button primary" data-submit>Save</button></div>
</form></dialog>
@endforeach
@endif
</section>
@endforeach
<p class="empty" data-no-sizes @if($groups->isNotEmpty()) hidden @endif>No matching sizes.</p>
</section>
@if(auth()->user()->allows('products.edit'))
<dialog class="size-add-dialog" id="all-size-add">
<header class="pricing-heading"><h2>Add size</h2><button type="button" class="button secondary" data-size-add-close>Cancel</button></header>
@if($products->isEmpty())
<p class="empty">Add a product before adding sizes.</p>
@else
<form method="POST" action="{{ route('products.sizes.store',$products->first()) }}" class="size-add-form" data-save-form data-directory-size-add>
@csrf<input type="hidden" name="return_to" value="all-sizes">
<label>Product<select data-add-size-product aria-label="Product" required>
@foreach($products as $product)<option data-companies="{{ $product->prices->unique('company')->reject(fn($p) => $p->removed)->pluck('company')->values()->toJson() }}" value="{{ route('products.sizes.store',$product) }}">{{ $product->name ?: 'Unnamed product' }}</option>@endforeach
</select></label>
<label>Company<input type="text" name="company" list="size-company-options" placeholder="Company name"><datalist id="size-company-options"></datalist></label>
@foreach(['size'=>'Size','manufacturer_price'=>'Manufacturer/Seller','difference'=>'Basic difference','cc'=>'CC','notes'=>'Notes'] as $field=>$label)
<label>{{ $label }}<input type="text" name="{{ $field }}" placeholder="{{ $label }}"></label>
@endforeach
@include('products.weight-input', ['weightValue'=>''])
<div class="size-edit-actions"><button class="button primary" data-submit>Save</button></div>
</form>
@endif
</dialog>
@endif
@include('products.size-delete-dialog')
<script src="{{ asset('js/size-editor.js') }}" defer></script>
<script src="{{ asset('js/weight-input.js') }}" defer></script>
<script src="{{ asset('js/all-sizes.js') }}" defer></script>
</x-app-layout>
