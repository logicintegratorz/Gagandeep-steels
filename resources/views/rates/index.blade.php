<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Manage freight rates, transporters and routes with Gagandeep Steels.">

    <title>{{ $editing ? 'Edit freight rate' : 'Freight rates' }} · Gagandeep Steels</title>

    <link rel="icon" href="{{ asset('images/gagandeep-steels-logo.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/feedback.css').'?v='.filemtime(public_path('css/feedback.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/rate-directory.css').'?v='.filemtime(public_path('css/rate-directory.css')) }}">

    <script src="{{ asset('js/app.js').'?v='.filemtime(public_path('js/app.js')) }}" defer></script>
    <script src="{{ asset('js/ui.js').'?v='.filemtime(public_path('js/ui.js')) }}" defer></script>
<script src="{{ asset('js/rate-filters.js').'?v='.filemtime(public_path('js/rate-filters.js')) }}" defer></script><link rel="stylesheet" href="{{ asset('css/compact.css').'?v='.filemtime(public_path('css/compact.css')) }}"></head>

<body>

<a class="skip-link" href="#main">Skip to content</a>

<x-sidebar/>
<x-screen-time/>

<main class="main" id="main">

    <header class="topbar">
        <div>
            <button
                class="icon-button mobile-toggle"
                id="menu-toggle"
                aria-label="Toggle navigation"
                aria-expanded="false"
                aria-controls="sidebar"
            >
                <x-icon name="menu"/>
            </button>

            <span>Workspace</span>

            <x-icon name="chevron" size="13"/>

            <strong>Freight rates</strong>
        </div>

        <span class="workspace-label">
            Freight workspace
        </span>

        <x-notification-bell/>
    </header>


    <div class="content">

        <!-- {{-- PAGE HEADING --}}
        <div class="page-heading">

            <div>
                <div class="eyebrow">
                    FREIGHT MANAGEMENT
                </div>

                <h1>
                    Freight rates<span>.</span>
                </h1>

                <p>
                    Every route. Every transporter. The right rate.
                </p>
            </div>

            <span class="currency">
                Currency <strong>INR ₹</strong>
            </span>

        </div> -->


        {{-- STATS --}}
        <!-- <section class="stats" aria-label="Rate summary">

            <div class="stat">

                <div>
                    Total rates
                    <x-icon name="route"/>
                </div>

                <strong>
                    {{ number_format($total) }}
                </strong>

                <small>
                    Your freight rate directory
                </small>

            </div> -->

            {{--

            <div class="stat">
                <div>
                    Transporters
                    <x-icon name="truck"/>
                </div>

                <strong>
                    {{ number_format($transporterCount) }}
                </strong>

                <small>
                    Registered transporters
                </small>
            </div>

            <div class="stat">
                <div>
                    Average rate
                    <x-icon name="rupee"/>
                </div>

                <strong>
                    {{ \App\Models\FreightRate::formatInr($average) }}
                </strong>

                <small>
                    Across all saved rates
                </small>
            </div>

            --}}

        </section>


        {{-- ADD / EDIT RATE FORM --}}
        @if($editing || auth()->user()->allows('rates.create'))

            <section
                class="panel {{ $editing ? 'editing' : '' }}"
                id="rate-form"
                aria-labelledby="form-title"
            >

                <!-- <div class="panel-heading">

                    <span class="section-icon">
                        <x-icon :name="$editing ? 'edit' : 'plus'"/>
                    </span>

                    <div>

                        <h2 id="form-title">
                            {{ $editing ? 'Edit freight rate' : 'Add freight rate' }}
                        </h2>

                        <p>
                            {{
                                $editing
                                    ? 'Update the details and save your changes.'
                                    : 'Set a rate for a route and transporter.'
                            }}
                        </p>

                    </div>

                    <span class="required-note">
                        * Required fields
                    </span>

                    @if($editing)

                        <a
                            class="text-link"
                            href="{{ route('rates.index') }}"
                        >
                            Cancel
                        </a>

                    @endif

                </div> -->


                <form
                    method="POST"
                    action="{{ $editing ? route('rates.update', $editing) : route('rates.store') }}"
                    class="rate-form"
                    data-save-form
                >

                    @csrf


                    @if($editing)

                        @method('PUT')

                        <input
                            type="hidden"
                            name="version"
                            value="{{ old('version', $editing->version) }}"
                        >

                    @endif


                    {{-- FROM --}}
                    <label for="origin">

                        From <span>*</span>

                        <input
                            id="origin"
                            name="origin"
                            value="{{ old('origin', $editing?->origin) }}"
                            placeholder="Origin"
                            maxlength="100"
                            required
                            @error('origin')
                                aria-invalid="true"
                            @enderror
                        >

                    </label>


                    {{-- TO --}}
                    <label for="destination">

                        To <span>*</span>

                        <input
                            id="destination"
                            name="destination"
                            value="{{ old('destination', $editing?->destination) }}"
                            placeholder="Destination"
                            maxlength="100"
                            required
                            @error('destination')
                                aria-invalid="true"
                            @enderror
                        >

                    </label>


                    {{-- PRICE --}}
                    <label for="price">

                        Price <span>*</span>

                        <input
                            id="price"
                            name="price"
                            value="{{ old('price', $editing?->price) }}"
                            placeholder="0.00"
                            inputmode="decimal"
                            pattern="[0-9]{1,9}(\.[0-9]{1,2})?"
                            title="A positive amount with at most two decimal places"
                            maxlength="12"
                            required
                            @error('price')
                                aria-invalid="true"
                            @enderror
                        >

                    </label>


                    {{-- TRANSPORTER --}}
                    <div class="transporter-field">

                        <label for="transporter">
                            Transporter <span>*</span>
                        </label>

                        <!-- <x-transporter-select
                            id="transporter"
                            :names="$transporters"
                            :selected="old('transporter', $editing?->transporter)"
                            :required="true"
                        /> -->
                            <x-transporter-select
    id="transporter"
    :names="collect($transporters)->sort(fn($a, $b) => strcasecmp($a, $b))->values()->all()"
    :selected="old('transporter', $editing?->transporter)"
    :required="true"
    placeholder="Transporter"
/>

                        @if(auth()->user()->allows('transporters.create'))

                            <a
                                class="field-help"
                                href="{{ route('transporters.index') }}"
                                target="_blank"
                                rel="noopener"
                            >
                                Add transporter ↗
                            </a>

                        @endif

                    </div>


                    {{-- REMARKS --}}
                    <label for="remarks">

                        Remarks

                        <input
                            id="remarks"
                            name="remarks"
                            value="{{ old('remarks', $editing?->remarks) }}"
                            placeholder="Notes"
                            maxlength="1000"
                        >

                    </label>


                    {{-- SUBMIT --}}
                    <!-- <button
                        type="submit"
                        class="button primary"
                        data-submit
                    >

                        <x-icon
                            :name="$editing ? 'check' : 'plus'"
                            size="17"
                        />

                        <span>
                            {{ $editing ? 'Save changes' : 'Rate' }}
                        </span>

                    </button> -->

                    <button
    type="submit"
    class="button primary"
    data-submit
>
    <span>
        {{ $editing ? 'Save changes' : 'Add' }}
    </span>
</button>

                </form>

            </section>

        @endif


        {{-- RATE DIRECTORY --}}
        <section
            class="panel directory"
            aria-labelledby="directory-title"
        >

            <div class="list-heading">

                <div>

                    <h2 id="directory-title">
                        Rate Directory
                    </h2>

                    <!-- <p>
                        Compare routes and find the rate you need.
                    </p> -->

                </div>


                {{-- RIGHT SIDE ACTIONS --}}
                <div class="directory-actions">

                    {{-- TOTAL RATE COUNT --}}
                    <span class="total-rate-count">

                        Total:

                        <strong>
                            {{ number_format($total) }}
                        </strong>

                    </span>


                    {{-- EXPORT CSV --}}
                    @if(auth()->user()->allows('rates.export'))

                        <a
                            class="button secondary"
                            data-csv-export
                            href="{{ route('rates.export', array_diff_key($filters, ['page' => true])) }}"
                        >

                            <x-icon
                                name="download"
                                size="16"
                            />

                            Export CSV

                        </a>

                    @endif

                </div>

            </div>


            {{-- FILTERS --}}
            <!-- <form
                method="GET"
                action="{{ route('rates.index') }}"
                class="filters"
            > -->
                <form
    method="GET"
    action="{{ route('rates.index') }}"
    class="filters"
    id="rate-filters"
>
                <!-- {{-- SEARCH --}}
                <label class="search-box">

                    <span class="sr-only">
                        Search rates
                    </span>

                    <x-icon
                        name="search"
                        size="17"
                    />

                    <input
                        type="search"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        maxlength="200"
                        placeholder="Search rates…"
                    >

                </label> -->


                {{-- FROM --}}
                <label>

                    <span class="sr-only">
                        Filter by origin
                    </span>

                    <!-- <input
                        name="from"
                        value="{{ $filters['from'] ?? '' }}"
                        placeholder="From"
                        maxlength="100"
                    > -->

                    <input
    id="filter-from"
    name="from"
    value="{{ $filters['from'] ?? '' }}"
    placeholder="From"
    maxlength="100"
    autocomplete="off"
>

                </label>


                {{-- TO --}}
                <label>

                    <span class="sr-only">
                        Filter by destination
                    </span>

                    <!-- <input
                        name="to"
                        value="{{ $filters['to'] ?? '' }}"
                        placeholder="To"
                        maxlength="100"
                    > -->

                    <input
    id="filter-to"
    name="to"
    value="{{ $filters['to'] ?? '' }}"
    placeholder="To"
    maxlength="100"
    autocomplete="off"
>

                </label>


                {{-- TRANSPORTER --}}
                <div class="transporter-filter">

                    <x-transporter-select
                        id="filter-transporter"
                        :names="$transporters"
                        :selected="$filters['transporter'] ?? ''"
                        placeholder="Transporters"
                        label="Filter by transporter"
                    />

                </div>


                {{-- SORT --}}
                <label>

                    <span class="sr-only">
                        Sort rates
                    </span>

                    <select name="sort">

                        <option
                            value="updated"
                            @selected(($filters['sort'] ?? 'updated') === 'updated')
                        >
                            Range   
                        </option>

                        <option
                            value="price_asc"
                            @selected(($filters['sort'] ?? '') === 'price_asc')
                        >
                            Price: low to high
                        </option>

                        <option
                            value="price_desc"
                            @selected(($filters['sort'] ?? '') === 'price_desc')
                        >
                            Price: high to low
                        </option>

                    </select>

                </label>


                


                <a
                    class="text-link"
                    href="{{ route('rates.index') }}"
                >
                    Clear
                </a>

            </form>


            {{-- TABLE --}}
            <div
                class="table-scroll"
                tabindex="0"
                role="region"
                aria-label="Freight rates table"
            >

                <table class="rate-list-table">

                    <thead>

                    <tr>

                        <th scope="col">S.No.</th><th scope="col">From</th><th scope="col">To</th>

                        <th scope="col">Price</th>

                        <th scope="col">
                            Transporter
                        </th>

                        <th scope="col">
    Last updated
</th>

<th scope="col">
    Remarks
</th>

                        <th scope="col">
                            <span class="sr-only">
                                Actions
                            </span>
                        </th>

                    </tr>

                    </thead>


                    <tbody>

                    @forelse($rates as $rate)

                        <tr>

                            {{-- ROUTE --}}
                            <td class="serial-cell">{{ $rates->firstItem() + $loop->index }}</td><td class="location-cell">{{ \Illuminate\Support\Str::ucfirst($rate->origin) }}</td><td class="location-cell">{{ \Illuminate\Support\Str::ucfirst($rate->destination) }}</td>


                            {{-- PRICE --}}
                            <td class="price-cell">
                                <span class="rate-price">{{ number_format((float) $rate->price, 2, '.', '') }}</span>
                            </td>


                            {{-- TRANSPORTER --}}
                            <td>

                                {{ $rate->transporter }}

                            </td>


                            {{-- UPDATED DATE --}}
                            <td class="date-cell">

                                <time
                                    datetime="{{ $rate->updated_at->toIso8601String() }}"
                                >
                                    {{ $rate->updated_at->format('Y-m-d') }}
                                </time>

                            </td>


                            {{-- REMARKS --}}
                            <td class="remarks-cell">
                                {{ $rate->remarks ?: '—' }}
                            </td>


                            {{-- ACTIONS --}}
                            <td>

                                <div class="row-actions">


                                    {{-- EDIT --}}
                                    @if(auth()->user()->allows('rates.edit'))

                                        <a
                                            class="icon-button"
                                            href="{{ route('rates.edit', $rate) }}#rate-form"
                                            aria-label="Edit {{ $rate->origin }} to {{ $rate->destination }} with {{ $rate->transporter }}"
                                            title="Edit rate"
                                        >

                                            <x-icon
                                                name="edit"
                                                size="16"
                                            />

                                        </a>

                                    @endif


                                    {{-- DELETE --}}
                                    @if(auth()->user()->allows('rates.delete'))

                                        <form
                                            method="POST"
                                            action="{{ route('rates.destroy', $rate) }}"
                                            data-delete-form
                                            data-description="{{ $rate->origin }} → {{ $rate->destination }} · {{ $rate->transporter }} · {{ $rate->formattedPrice() }}"
                                        >

                                            @csrf
                                            @method('DELETE')


                                            <input
                                                type="hidden"
                                                name="version"
                                                value="{{ $rate->version }}"
                                            >


                                            <button
                                                type="submit"
                                                class="icon-button delete-button"
                                                data-delete-trigger
                                                disabled
                                                title="Delete rate"
                                                aria-label="Delete {{ $rate->origin }} to {{ $rate->destination }} with {{ $rate->transporter }}"
                                            >

                                                <x-icon
                                                    name="trash"
                                                    size="16"
                                                />

                                            </button>

                                        </form>

                                    @endif

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td colspan="8">

                                <div class="empty">

                                    <x-icon
                                        name="route"
                                        size="32"
                                    />


                                    <h3>
                                        {{
                                            $total
                                                ? 'No matching rates'
                                                : 'Your rate book starts here'
                                        }}
                                    </h3>


                                    <p>
                                        {{
                                            $total
                                                ? 'Try another city or transporter, or clear your filters.'
                                                : 'Add your first freight rate using the form above.'
                                        }}
                                    </p>


                                    @if($total)

                                        <a
                                            class="button secondary"
                                            href="{{ route('rates.index') }}"
                                        >
                                            Clear filters
                                        </a>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>


            {{-- TABLE FOOTER --}}
            <div class="table-footer">

                <span>

                    {{ $rates->firstItem() ?? 0 }}
                    –
                    {{ $rates->lastItem() ?? 0 }}

                    of

                    {{ number_format($rates->total()) }}

                    rates

                </span>


                <nav
                    aria-label="Rate pages"
                    class="pagination"
                >

                    {{-- PREVIOUS --}}
                    @if($rates->onFirstPage())

                        <span
                            class="page-button disabled"
                            aria-disabled="true"
                        >
                            ‹
                        </span>

                    @else

                        <a
                            class="page-button"
                            href="{{ $rates->previousPageUrl() }}"
                            aria-label="Previous page"
                        >
                            ‹
                        </a>

                    @endif


                    <span>

                        Page
                        {{ $rates->currentPage() }}

                        of

                        {{ $rates->lastPage() }}

                    </span>


                    {{-- NEXT --}}
                    @if($rates->hasMorePages())

                        <a
                            class="page-button"
                            href="{{ $rates->nextPageUrl() }}"
                            aria-label="Next page"
                        >
                            ›
                        </a>

                    @else

                        <span
                            class="page-button disabled"
                            aria-disabled="true"
                        >
                            ›
                        </span>

                    @endif

                </nav>

            </div>

        </section>


        <x-footer/>


        <noscript>

            <p class="notice">
                Enable JavaScript to delete rates with confirmation and use
                mobile navigation. Adding, editing, searching and exporting
                work without it.
            </p>

        </noscript>

    </div>

</main>


{{-- DELETE DIALOG --}}
<dialog
    id="delete-dialog"
    aria-labelledby="delete-title"
    aria-describedby="delete-description"
>

    <div class="dialog-content">

        <span class="delete-symbol">

            <x-icon
                name="trash"
                size="24"
            />

        </span>


        <h2 id="delete-title">
            Delete this freight rate?
        </h2>


        <p id="delete-description"></p>


        <p>
            This permanently removes the rate from your directory.
        </p>


        <div class="dialog-actions">

            <button
                type="button"
                class="button secondary"
                id="cancel-delete"
                autofocus
            >
                Keep rate
            </button>


            <button
                type="button"
                class="button danger"
                id="confirm-delete"
            >
                Delete rate
            </button>

        </div>

    </div>

</dialog>


<x-toasts/>


<style>
    /* Total rate count + export CSV alignment */
    .directory-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .total-rate-count {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 9px 13px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f9fafb;
        color: #6b7280;
        font-size: 14px;
        line-height: 1;
        white-space: nowrap;
    }

    .total-rate-count strong {
        color: #111827;
        font-size: 15px;
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .directory-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }
</style>

</body>
</html>
