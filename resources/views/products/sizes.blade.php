<x-app-layout :title="($product->name ?: 'Product').' · Sizes'">

<link rel="stylesheet" href="{{ asset('css/products.css').'?v='.filemtime(public_path('css/products.css')) }}">

<script src="{{ asset('js/product-sizes.js').'?v='.filemtime(public_path('js/product-sizes.js')) }}" defer></script>

@include('products.size-details')
</x-app-layout>
