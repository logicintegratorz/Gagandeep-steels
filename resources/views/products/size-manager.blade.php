@if($product && $price && auth()->user()->allows('products.edit'))

<dialog
    class="size-add-dialog"
    id="size-add-{{ $product->id }}"
    aria-labelledby="add-size-title-{{ $product->id }}"
>
    <header class="pricing-heading">
        <h2 id="add-size-title-{{ $product->id }}">Add size</h2>
    </header>

    <form
        method="POST"
        action="{{ route('products.sizes.store', $product) }}"
        class="size-add-form"
        novalidate
        data-save-form
    >
        @csrf

        @if($inline ?? false)
            <input type="hidden" name="return_to" value="rates">
        @endif

        <input type="hidden" name="price" value="{{ $price->id }}">
        <input
            type="hidden"
            name="date"
            value="{{ $date ?? now('Asia/Kolkata')->toDateString() }}"
        >

        <label>
            Enter size

            <input
                type="text"
                name="size"
                value="{{ old('size') }}"
                placeholder="e.g. 25 × 5"
            >
        </label>

        <label>
            Base price

            <input
                type="text"
                name="base_price"
                value="{{ old('base_price') }}"
                placeholder="Enter size base price"
            >
        </label>

        <label>Manufacturer/Seller<input type="text" name="manufacturer_price" value="{{ old('manufacturer_price') }}" placeholder="Manufacturer/Seller"></label>

        <label>
            Basic difference

            <input
                type="text"
                name="difference"
                value="{{ old('difference') }}"
                placeholder="Enter difference"
            >
        </label>

        @include('products.weight-input', ['weightValue'=>old('weight', '')])

        <label>
            CC

            <input
                type="text"
                name="cc"
                value="{{ old('cc') }}"
                placeholder="Enter CC"
            >
        </label>

        <label>
            Notes

            <input
                type="text"
                name="notes"
                value="{{ old('notes') }}"
                placeholder="Notes"
            >
        </label>

        <div class="size-edit-actions">

            <button
                type="button"
                class="button secondary"
                data-size-add-close
            >
                Cancel
            </button>

            <button
                type="submit"
                class="button primary"
                data-submit
            >
                Add size
            </button>

        </div>

    </form>
</dialog>

@endif


@if($product && isset($sizes))

<section
    class="panel"
    data-size-manager
    data-base-rate="{{ $price->retail_price ?? $price->purchase_price ?? '' }}"
>

    <header class="pricing-heading">

        <div class="saved-sizes-heading">
            <h2>Search</h2>
        </div>



    </header>


    <div class="table-scroll"><table class="size-directory size-selection-table"><thead><tr><th>Size</th><th>Basic Price</th><th>Basic difference</th><th>CC</th><th>Size price</th><th>Weight</th><th>Notes</th></tr></thead><tbody><tr>
    <td><input type="text" data-size-picker-search aria-label="Search sizes for {{ $product->name }}" placeholder="Search size" autocomplete="off"><select hidden tabindex="-1" aria-hidden="true" data-size-picker aria-label="Select size for {{ $product->name }}"><option value="">Select size</option>
    @foreach($sizes as $size)<option data-company="{{ $size->company ?? '' }}" value="{{ $size->id }}" data-difference="{{ $size->difference }}" data-cc="{{ $size->cc }}" data-weight="{{ \App\Support\WeightDisplay::format($size->weight) }}" data-notes="{{ $size->notes }}">{{ $size->size ?: 'Unnamed size' }}</option>@endforeach
    </select><small data-size-picker-status role="status"></small></td>
    <td><input type="text" data-picked-basic aria-label="Basic Price" readonly></td><td><input type="text" data-picked-difference aria-label="Basic difference" readonly></td><td><label class="size-cc-toggle"><input type="checkbox" data-include-cc aria-label="Include CC in size price" disabled><input type="text" data-picked-cc aria-label="CC" readonly></label></td><td><input type="text" data-picked-price aria-label="Size price" readonly></td><td><textarea class="weight-readonly" data-picked-weight aria-label="Weight" readonly rows="2"></textarea></td><td><input type="text" data-picked-notes aria-label="Notes" readonly></td>
    </tr></tbody></table></div>
    <div class="table-scroll" data-size-company-results hidden>
        <table class="size-directory"><thead><tr><th>Company name</th><th>Size</th><th>Basic Price</th><th>Basic difference</th><th>CC</th><th>Size price</th><th>Weight</th><th>Notes</th></tr></thead><tbody data-size-company-rows></tbody></table>
        <p class="empty" data-size-company-empty hidden>No matching sizes.</p>
    </div>

</section>
<section class="panel saved-size-panel" data-size-manager data-base-rate="{{ $price->retail_price ?? $price->purchase_price ?? '' }}">
    <header class="pricing-heading">
        <div class="saved-sizes-heading size-list-heading">
            <h2>Size List</h2>
            @foreach(($activities ?? collect())->unique('company') as $latestActivity)
                <p class="size-last-activity" data-company-activity="{{ $latestActivity->company ?? '' }}" @if(($latestActivity->company ?? '') !== ($price->company ?? '')) hidden @endif>Last Update {{ $latestActivity->occurred_at->timezone('Asia/Kolkata')->format('d M Y, H:i') }} · by {{ $latestActivity->actor_name }}</p>
            @endforeach
        </div>
        @if(auth()->user()->allows('products.edit'))

            <button
                type="button"
                class="button primary"
                data-size-add-open="size-add-{{ $product->id }}"
            >
                Add size
            </button>

        @endif
    </header>
    <div class="table-scroll">

        <div class="size-price-tabs" role="group" aria-label="Size price list">
<button type="button" class="button secondary" data-size-price-tab="manufacturer" aria-pressed="true">Manufacturer/Seller</button>
<button type="button" class="button secondary" data-size-price-tab="godown" aria-pressed="false">Godown Price</button>
</div>
<table class="size-directory saved-size-table">

            <thead>

                <tr>

                    <th>Size</th>

                    <th>Basic Difference</th>

                    <th>CC</th>
                    <th data-location-price-heading>Manufacturer/Seller</th>

                    <th>Weight</th>

                    <th>Notes</th>

                    @if(auth()->user()->allows('products.edit'))
                        <th>Actions</th>
                    @endif

                </tr>

            </thead>


            <tbody>

                @foreach($sizes as $size)

                    <tr
                        data-size-list-row
                        data-size="{{ $size->size }}" data-company="{{ $size->company ?? '' }}"
                        data-difference="{{ $size->difference }}"
                    >

                        <td>
                            {{ $size->size }}
                        </td>

                        <td>
                            {{ $size->difference }}
                        </td>

                        <td>
                            {{ $size->cc }}
                        </td>
                        <td data-location-price data-manufacturer="{{ $size->manufacturer_price }}" data-godown="{{ $size->godown_price }}">{{ $size->manufacturer_price ?? '—' }}</td>

                        <td>
                            @include('products.weight-display', ['weightValue'=>$size->weight])
                        </td>

                        <td>
                            {{ $size->notes }}
                        </td>


                        @if(auth()->user()->allows('products.edit'))

                            <td>

                                <div class="size-row-actions">

                                    <button
                                        type="button"
                                        class="button secondary"
                                        data-size-edit-open="size-editor-{{ $size->id }}"
                                        aria-controls="size-editor-{{ $size->id }}"
                                        aria-expanded="false"
                                    >
                                        Edit
                                    </button>


                                    <form
                                        method="POST"
                                        action="{{ route('products.sizes.destroy', [$product, $size]) }}"
                                        data-delete-form
                                        data-description="Size: {{ $size->size }}. This permanently removes this size from the list and dropdown."
                                    >

                                        @csrf
                                        @method('DELETE')

                                        @include('products.size-context')

                                        <button
                                            class="button danger"
                                            data-delete-trigger
                                            disabled
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        @endif

                    </tr>

                @endforeach


                <tr
                    data-size-list-empty
                    @if($sizes->isNotEmpty()) hidden @endif
                >

                    <td
                        colspan="{{ auth()->user()->allows('products.edit') ? 7 : 6 }}"
                    >
                        No matching sizes.
                    </td>

                </tr>

            </tbody>

        </table>

    </div>


    @if(auth()->user()->allows('products.edit'))

        @foreach($sizes as $size)

            <div
                id="size-editor-{{ $size->id }}"
                class="size-editor-panel"
                data-size-editor
                hidden
            >

                <header class="pricing-heading">

                    <h2>
                        Edit size: {{ $size->size ?: 'Unnamed size' }}
                    </h2>

                </header>


                <form
                    method="POST"
                    action="{{ route('products.sizes.update', [$product, $size]) }}"
                    class="size-add-form size-edit-form"
                    novalidate
                    data-save-form
                >

                    @csrf
                    @method('PUT')

                    @include('products.size-context')


                    @foreach([
                        'size' => 'Size',
                        'manufacturer_price' => 'Manufacturer/Seller',
                        'base_price' => 'Base price',
                        'difference' => 'Basic difference',
                        'cc' => 'CC',
                        
                        'notes' => 'Notes'
                    ] as $field => $label)

                        <label>

                            {{ $label }}

                            <input
                                type="text"
                                name="{{ $field }}"
                                value="{{ $size->$field }}"
                            >

                        </label>

                    @endforeach


                    @include('products.weight-input', ['weightValue'=>$size->weight])
                    <div class="size-edit-actions">

                        <button
                            type="button"
                            class="button secondary"
                            data-size-edit-cancel
                        >
                            Cancel
                        </button>

                        <button
                            class="button primary"
                            data-submit
                        >
                            Save changes
                        </button>

                    </div>

                </form>

            </div>

        @endforeach

    @endif

</section>

@endif


@once

<script
    src="{{ asset('js/size-editor.js').'?v='.filemtime(public_path('js/size-editor.js')) }}"
    defer
></script>

@endonce


@once
<script src="{{ asset('js/weight-input.js').'?v='.filemtime(public_path('js/weight-input.js')) }}" defer></script>
@endonce
