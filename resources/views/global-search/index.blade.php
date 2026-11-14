<x-app-layout title="Global search">
    <link rel="stylesheet" href="{{ asset('css/global-search.css').'?v='.filemtime(public_path('css/global-search.css')) }}">

    <section class="global-search-page">
        <header class="global-search-heading">
            <div>
                <span class="eyebrow">CRM SEARCH</span>
                <h1>Find CRM records<span>.</span></h1>
                <p>Search accessible leads and products by name, company, mobile number, alternate number, WhatsApp number or product.</p>
            </div>
        </header>

        <form class="global-search-form" action="{{ route('global-search.index') }}" method="GET">
            <label for="global-search-input">Search CRM</label>
            <div>
                <input id="global-search-input" type="search" name="q" value="{{ $term }}" maxlength="150" autocomplete="off" autofocus placeholder="Name, mobile number, company or product">
                <button class="button primary">Search</button>
            </div>
        </form>

        @if($term === '')
            <div class="global-search-empty">Enter a name, mobile number, alternate number, company or product to search.</div>
        @else
            <p class="global-search-count">Results for <strong>“{{ $term }}”</strong>: {{ $leads->count() }} leads and {{ $products->count() }} products.</p>

            <section class="global-search-section" aria-labelledby="lead-results-title">
                <header><h2 id="lead-results-title">Lead records <span>{{ $leads->count() }}</span></h2><p>Only leads you are allowed to view are shown.</p></header>
                @if($leads->isEmpty())
                    <p class="global-search-none">No accessible lead matched this search.</p>
                @else
                    <div class="table-scroll"><table class="global-search-table"><thead><tr><th>Name</th><th>Company</th><th>Numbers</th><th>Stage</th><th>Owner</th><th>Record activity</th><th></th></tr></thead><tbody>
                    @foreach($leads as $lead)
                        <tr>
                            <td><strong>{{ $lead->name }}</strong><small>{{ $lead->email ?: 'No email' }}</small></td>
                            <td>{{ $lead->company ?: '—' }}</td>
                            <td>
                                <span>{{ $lead->phone ?: '—' }}</span>
                                @if($lead->alt_phone)
                                    <small>Alt: {{ $lead->alt_phone }}</small>
                                @endif
                                @if($lead->whatsapp)
                                    <small>WhatsApp: {{ $lead->whatsapp }}</small>
                                @endif
                            </td>
                            <td>{{ $lead->stage }}</td>
                            <td>{{ $lead->owner?->name ?? 'Unassigned' }}</td>
                            <td>{{ $lead->activities_count }} activities · {{ $lead->work_items_count }} work items</td>
                            <td><a class="text-link" href="{{ route('leads.show', ['lead' => $lead, 'from' => 'dashboard']) }}">Open lead →</a></td>
                        </tr>
                    @endforeach
                    </tbody></table></div>
                @endif
            </section>

            @if(auth()->user()->allows('products.view'))
                <section class="global-search-section" aria-labelledby="product-results-title">
                    <header><h2 id="product-results-title">Products <span>{{ $products->count() }}</span></h2><p>Active products matching the search.</p></header>
                    @if($products->isEmpty())
                        <p class="global-search-none">No active product matched this search.</p>
                    @else
                        <div class="table-scroll"><table class="global-search-table"><thead><tr><th>Product</th><th>Code</th><th>Category</th><th>Unit</th><th></th></tr></thead><tbody>
                        @foreach($products as $product)
                            <tr>
                                <td><strong>{{ $product->name }}</strong></td>
                                <td>{{ $product->item_code }}</td>
                                <td>{{ $product->category ?: '—' }}</td>
                                <td>{{ $product->unit }}</td>
                                <td><a class="text-link" href="{{ route('products.pricing') }}">View rates →</a></td>
                            </tr>
                        @endforeach
                        </tbody></table></div>
                    @endif
                </section>
            @endif
        @endif
    </section>
</x-app-layout>
