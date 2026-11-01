<details class="company-picker" data-company-picker hidden>
<summary aria-label="Company rates for {{ $product->name }}"><span data-company-caption>{{ $first->company ?: 'Select company' }}</span></summary>
<div class="company-options" id="company-options-{{ $product->id }}">
@foreach($prices as $price)
<button type="button" data-company-choice="{{ $price->id }}"><strong>{{ \App\Support\PriceDisplay::format($price->retail_price ?? $price->purchase_price ?? '—') }}</strong><span>{{ $price->company ?: 'Unspecified company' }}</span><small>{{ $price->updated_at->timezone('Asia/Kolkata')->format('H:i') }}</small></button>
@endforeach
</div></details>
@if(auth()->user()->allows('products.edit') && $date === now('Asia/Kolkata')->toDateString())
<dialog id="company-edit-{{ $product->id }}" class="company-price-dialog"><header class="pricing-heading"><h2>{{ $product->name }}</h2><button type="button" class="button secondary" data-company-add aria-label="Add company row">+</button></header>
<form method="POST" action="{{ route('products.company-prices',$product) }}" data-save-form>
@csrf<input type="hidden" name="version" value="{{ $product->version }}">
<div data-company-rows>
@foreach($prices as $i=>$price)
<div class="company-edit-row" data-price-id="{{ $price->id }}"><input type="radio" name="selected_row" value="{{ $i }}" aria-label="Select company row" @checked($loop->first)><label><span class="sr-only">Price</span><input type="text" placeholder="Price" name="rows[{{ $i }}][price]" value="{{ \App\Support\PriceDisplay::format($price->retail_price ?? $price->purchase_price ?? '') }}"></label><label><span class="sr-only">Company name</span><input type="text" placeholder="Company name" name="rows[{{ $i }}][company]" value="{{ $price->company }}"></label><button type="button" class="button danger" data-company-remove aria-label="Remove company row">−</button></div>
@endforeach
</div>

<div class="company-editor-actions"><button type="button" class="button secondary" data-company-cancel>Cancel</button><button class="button primary" data-submit>Save</button></div>
</form>
<template><div class="company-edit-row"><input type="radio" name="selected_row" aria-label="Select company row"><label><span class="sr-only">Price</span><input type="text" placeholder="Price" data-field="price"></label><label><span class="sr-only">Company name</span><input type="text" placeholder="Company name" data-field="company"></label><button type="button" class="button danger" data-company-remove aria-label="Remove company row">−</button></div></template>
</dialog>
@endif
