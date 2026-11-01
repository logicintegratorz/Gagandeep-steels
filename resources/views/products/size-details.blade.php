<section class="panel" data-size-workspace data-base-rate="{{ \App\Support\PriceDisplay::format($price->retail_price ?? $price->purchase_price ?? '') }}" data-selected-size="{{ request('selected') }}">

    <header class="size-detail-header">

        <div><h2>{{ $product->name ?: 'Product' }}</h2><p class="size-base-price"><span data-detail-rate>{{ \App\Support\PriceDisplay::format($price->retail_price ?? $price->purchase_price ?? '—') }}</span> <small>/ {{ $product->unit }}</small></p><p class="size-company" data-detail-company>{{ $price->company ?: 'Unspecified company' }} · {{ $price->effective_date->format('d M Y') }}</p></div>

        <label class="size-header-search">Search sizes<input type="text" data-size-search placeholder="Search saved sizes" autocomplete="off"></label>

        <div class="size-hsn"><span>HSN code</span><strong>{{ $product->hsn_sac ?: '—' }}</strong></div>

    </header>

    <div class="size-fields-heading"><h3>{{ $product->name ?: 'Product' }}</h3></div>

    <div class="size-selection">

        <label>Size<select data-size-select><option value="">Select size</option>@foreach($sizes as $size)<option value="{{ $size->id }}" data-size="{{ $size->size }}" data-base-price="{{ $size->base_price }}" data-difference="{{ $size->difference }}" data-cc="{{ $size->cc }}" data-weight="{{ \App\Support\WeightDisplay::format($size->weight) }}">{{ $size->size ?: 'Unnamed size' }}</option>@endforeach</select></label>

        <label>Size base price<input type="text" data-size-base-price readonly value="{{ \App\Support\PriceDisplay::format($price->retail_price ?? $price->purchase_price ?? '') }}" placeholder="—"></label>

        <label>Basic difference<input type="text" data-size-difference readonly placeholder="—"></label>

        <label>CC<input type="text" data-size-cc readonly placeholder="—"></label>

        <label>Size price<input type="text" data-size-price readonly placeholder="—"></label>

        <label>Weight<textarea class="weight-readonly" data-size-weight readonly rows="2"></textarea></label>

        <label>Notes<input type="text" data-working-notes placeholder="Your notes"></label>

    </div>

    <p class="pricing-hint" data-size-status role="status">Select a saved size to load its details.</p>

    <div data-related-sizes hidden>
        <header class="pricing-heading"><h2>Related sizes</h2><span data-related-count role="status"></span></header>
        <div class="table-scroll" tabindex="0" role="region" aria-label="Related sizes">
            <table class="size-directory">
                <thead><tr><th>Size</th><th>Base price</th><th>Size difference</th><th>CC</th><th>Size price</th><th>Weight</th><th>Notes</th></tr></thead>
                <tbody>
                @foreach($sizes as $size)
                    <tr data-related-row data-size="{{ $size->size }}" data-base-price="{{ $size->base_price }}" data-difference="{{ $size->difference }}" hidden>
                        <td>{{ $size->size ?: 'Unnamed size' }}</td><td>{{ \App\Support\PriceDisplay::format($size->base_price ?? 'Not set') }}</td><td>{{ \App\Support\PriceDisplay::format($size->difference ?? '—') }}</td><td>{{ $size->cc ?? '—' }}</td>
                        <td data-related-price></td><td>@include('products.weight-display', ['weightValue'=>$size->weight])</td><td>{{ $size->notes ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

</section>
