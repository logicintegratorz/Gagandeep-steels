<x-app-layout title="Product rates">
<link rel="stylesheet" href="{{ asset('css/products.css').'?v='.filemtime(public_path('css/products.css')) }}">
<script src="{{ asset('js/product-pricing.js').'?v='.filemtime(public_path('js/product-pricing.js')) }}" defer></script>
<section class="panel pricing-panel" data-live-pricing="{{ $date === now('Asia/Kolkata')->toDateString() ? 'true' : 'false' }}" aria-labelledby="pricing-title">
    <header class="pricing-heading"><h2 id="pricing-title">{{ $date === now('Asia/Kolkata')->toDateString() ? 'Today’s Pricing' : 'Pricing · '.\Carbon\Carbon::parse($date)->format('d M Y') }}</h2><div class="pricing-actions">@if(auth()->user()->allows('products.create'))<a class="button primary" href="{{ route('products.create') }}">+ Add product &amp; rate</a>@endif @if(auth()->user()->allows('products.view'))<a class="button secondary" href="{{ route('products.index') }}">Manage products</a>@endif<a class="button secondary" href="{{ route('products.logs') }}">Global log sheet</a></div></header>
    <div class="pricing-datebar">@if($date > '2000-01-01')<a class="button secondary pricing-previous" href="{{ route('products.pricing', ['date' => $previousDate, 'selected'=>$selected]) }}"><span aria-hidden="true">←</span> Previous date</a>@endif<div class="pricing-clock"><h3 class="pricing-clock-heading">Current date and time</h3><time data-pricing-clock>{{ now('Asia/Kolkata')->format('d/m/Y H:i') }}</time></div><form method="GET" action="{{ route('products.pricing') }}" class="pricing-date-form">@foreach($selected as $selectedId)<input type="hidden" name="selected[]" value="{{ $selectedId }}">@endforeach<label for="pricing-date">Pricing date<input type="date" id="pricing-date" name="date" value="{{ $date }}" min="2000-01-01" max="{{ now('Asia/Kolkata')->toDateString() }}" required></label><button class="button secondary" data-date-submit>View</button></form></div>
    @if($products->isNotEmpty())
    <div class="product-pricing-scroll" tabindex="0" role="region" aria-label="Product prices"><div class="product-pricing-grid">
        @foreach($pricingSlots as $slotIndex=>$product)
        @if(!$product)
        <article class="product-price-column empty-product-slot"><input type="checkbox" class="product-slot-checkbox" data-slot-directory="{{ route('products.index',['slot'=>$slotIndex]) }}" aria-label="Choose product for empty position {{ $slotIndex+1 }}"></article>
        @continue
        @endif
        @php($prices = $product->prices->unique('company')->sortBy('company')->values())
        @php($first = $prices->first())
        <article class="product-price-column" data-price-column>
            <h3><input type="checkbox" class="product-slot-checkbox" data-slot-directory="{{ route('products.index',['slot'=>$slotIndex]) }}" aria-label="Change {{ $product->name }} selection"><a data-product-id="{{ $product->id }}" aria-expanded="false" aria-controls="product-detail-{{ $product->id }}" data-size-link href="{{ route('products.sizes', ['product'=>$product, 'price'=>$first->id, 'date'=>$date]) }}">{{ $product->name ?: 'Unnamed product' }}</a></h3>
            <div class="product-price-value" @if(auth()->user()->allows('products.edit') && $date === now('Asia/Kolkata')->toDateString()) data-rate-trigger role="button" tabindex="0" aria-haspopup="dialog" aria-expanded="false" aria-controls="company-edit-{{ $product->id }}" aria-label="Edit company prices for {{ $product->name }}" @endif><output data-price-output>{{ \App\Support\PriceDisplay::format($first->retail_price ?? $first->purchase_price ?? '—') }}</output></div>
            <label><span class="sr-only">Company for {{ $product->name }}</span><select data-company-select aria-label="Company for {{ $product->name }}">@foreach($prices as $price)<option value="{{ $price->id }}" data-amount="{{ \App\Support\PriceDisplay::format($price->retail_price ?? $price->purchase_price ?? '—') }}" data-rate-day="{{ $price->effective_date->format('Y-m-d') }}" data-effective="{{ $price->effective_date->format('d M Y') }}" data-company="{{ $price->company }}" data-changed="{{ $price->updated_at->timezone('Asia/Kolkata')->format('d M Y H:i:s') }}" data-basis="{{ $price->retail_price !== null ? 'Retail' : 'Purchase' }}">{{ $price->company }}</option>@endforeach</select></label>
@include('products.company-price-editor')
        </article>
        @endforeach
    </div></div>
    <p class="sr-only" role="status" data-pricing-status></p>
    <noscript><p class="pricing-hint">Enable JavaScript to switch between company rates.</p></noscript>
    @else<div class="empty"><h3>No prices available for this date</h3><p>Add an active product with a dated price, or select another date.</p></div>@endif
</section>
<script src="{{ asset('js/product-sizes.js').'?v='.filemtime(public_path('js/product-sizes.js')) }}" defer></script>
@foreach($products as $product)
<div id="product-detail-{{ $product->id }}" data-inline-detail="{{ $product->id }}" data-reopen="{{ session('size_product') == $product->id ? session('size_price') : '' }}" data-new-size="{{ session('size_product') == $product->id ? session('size_id') : '' }}" hidden>
@include('products.related-size-list', ['price'=>$product->prices->unique('company')->sortBy('company')->first(), 'sizes'=>$productSizes->get($product->id, collect())])
@include('products.size-manager', ['activities'=>$sizeActivities->get($product->id, collect()), 'inline'=>true, 'price'=>$product->prices->unique('company')->sortBy('company')->first(), 'sizes'=>$productSizes->get($product->id, collect())])
</div>
@endforeach
@include('products.size-delete-dialog')
<script src="{{ asset('js/company-prices.js').'?v='.filemtime(public_path('js/company-prices.js')) }}" defer></script>
</x-app-layout>
