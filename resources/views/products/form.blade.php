<x-app-layout :title="$product ? 'Edit product' : 'Add product'">
<link rel="stylesheet" href="{{ asset('css/products.css').'?v='.filemtime(public_path('css/products.css')) }}">
<section class="panel product-form-panel" aria-labelledby="item-information">
    <header class="product-form-heading"><h2 id="item-information">Item information</h2><a class="text-link" href="{{ route('products.index') }}">Back to products</a></header>
    <form method="POST" action="{{ $product ? route('products.update', $product) : route('products.store') }}" enctype="multipart/form-data" class="product-form" novalidate data-save-form>
        @csrf @if($product) @method('PUT')<input type="hidden" name="version" value="{{ old('version', $product->version) }}">@endif
        <label class="product-full">Item name<input name="name" value="{{ old('name', $product?->name) }}" placeholder="e.g. Apollo Binding Wire"></label>
        <label>Item ID<input name="item_code" value="{{ old('item_code', $product?->item_code) }}" placeholder="Item ID"></label>
        <label>HSN/SAC<input name="hsn_sac" value="{{ old('hsn_sac', $product?->hsn_sac) }}" placeholder="HSN/SAC"></label>
        <label>Item type<select name="item_type">@foreach(['Product','Service'] as $type)<option @selected(old('item_type', $product?->item_type ?? 'Product') === $type)>{{ $type }}</option>@endforeach</select></label>
        <label>Category<input name="category" value="{{ old('category', $product?->category) }}" list="product-categories" placeholder="Select or enter category"><datalist id="product-categories">@foreach(['Steel','Iron','Scrap','Wire','Welding','Other'] as $category)<option value="{{ $category }}">@endforeach</datalist></label>
        <label>Units<select name="unit">@foreach(['Each','Kg','Tonne','Quintal','Metre','Bundle','Piece','Litre','Hour'] as $unit)<option @selected(old('unit', $product?->unit ?? 'Each') === $unit)>{{ $unit }}</option>@endforeach</select></label>
        <label>Tax type<input name="tax_type" value="{{ old('tax_type', $product?->tax_type) }}" placeholder="e.g. GST 18%"><small>Label only; prices are stored as entered.</small></label>
        <label class="product-full">Item description<textarea name="description" rows="3" placeholder="Item description">{{ old('description', $product?->description) }}</textarea></label>
        <label>Company<input name="company" value="{{ old('company', $price?->company) }}" placeholder="Supplier / company name"></label>
        <label>Price effective date<input type="date" name="effective_date" value="{{ old('effective_date', now('Asia/Kolkata')->toDateString()) }}" min="2000-01-01" max="{{ now('Asia/Kolkata')->toDateString() }}"></label>
        <label>Purchase rate<input type="text" name="purchase_price" value="{{ old('purchase_price', $price?->purchase_price) }}" placeholder="Enter rate"></label>
        <label>Retail rate<input type="text" name="retail_price" value="{{ old('retail_price', $price?->retail_price) }}" placeholder="Enter rate"><small>Leave blank to display purchase price.</small></label>
        <label class="product-full">Status<select name="active"><option value="1" @selected((string) old('active', $product ? (int)$product->active : 1) === '1')>Active</option><option value="0" @selected((string) old('active', $product ? (int)$product->active : 1) === '0')>Inactive</option></select><small>Inactive products are hidden from the pricing page.</small></label>
        <label class="product-full">Picture<input type="file" name="picture" accept="image/jpeg,image/png,image/gif"><small>JPG, PNG or GIF · maximum 2 MB, 6000 × 6000 pixels.</small>@if($product?->picture_path)<img class="product-thumbnail" src="{{ route('products.picture', $product) }}" alt="{{ $product->name }}">@endif</label>
        @if($product)<p class="product-full product-save-note">Saving a different company or date adds a price entry. Changes are recorded in the rate log, including changes to the same company and date.</p>@endif
        <div class="product-full product-form-actions"><a class="button secondary" href="{{ route('products.index') }}">Cancel</a><button type="submit" class="button primary" data-submit>{{ $product ? 'Save changes' : 'Add product' }}</button></div>
    </form>
</section>
@include('products.size-manager')
<script src="{{ asset('js/product-sizes.js').'?v='.filemtime(public_path('js/product-sizes.js')) }}" defer></script>
@include('products.size-delete-dialog')
</x-app-layout>
