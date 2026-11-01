<x-app-layout title="Global log sheet">
<link rel="stylesheet" href="{{ asset('css/products.css').'?v='.filemtime(public_path('css/products.css')) }}">
<section class="panel">
    <header class="pricing-heading"><div><h2>Global log sheet</h2><p>Product-wise rate history</p></div><a class="button secondary" href="{{ route('products.pricing') }}">Back to rates</a></header>
    <form class="filters rate-log-filters" data-rate-log-filters method="GET" action="{{ route('products.logs') }}">
        <label>Product/Company<input name="company" value="{{ $filters['company'] ?? '' }}" placeholder="Search product or company"></label>
        <label>Date filter<input type="date" name="date" data-log-date value="{{ $filters['date'] ?? '' }}"></label>
        <label>From date<input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
        <label>To date<input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
        <a class="text-link" href="{{ route('products.logs') }}">Clear</a>
    </form>
    <p class="pricing-hint"></p>
    <div data-rate-log-results aria-live="polite">
    @forelse($logs->getCollection()->groupBy('product_id') as $companyLogs)
    <section class="company-log-group" aria-label="{{ $companyLogs->first()->product_name ?: 'Unnamed product' }} rate history">
        <h3 class="company-log-heading"><x-icon name="box" size="18"/>{{ $companyLogs->first()->product_name ?: 'Unnamed product' }}</h3>
        <div class="table-scroll" tabindex="0" role="region" aria-label="Product rate changes"><table class="rate-log-table"><thead><tr><th scope="col">Product rate</th><th scope="col">Party name</th><th scope="col">Edited date &amp; time</th><th scope="col">Edited by</th><th scope="col">Entry</th></tr></thead><tbody>
            @foreach($companyLogs as $log)
            <tr>
                <td>{{ \App\Support\PriceDisplay::format($log->retail_price ?? $log->purchase_price ?? '—') }}</td>
                <td>{{ $log->company ?: 'Unspecified company' }}</td>
                <td>{{ $log->changed_at->timezone('Asia/Kolkata')->format('d M Y H:i') }}</td>
                <td>{{ $log->changed_by ?? 'Not recorded' }}</td>
                <td>{{ $log->event }}</td>
            </tr>
            @endforeach
        </tbody></table></div>
    </section>
    @empty<div class="empty"><h3>No rate logs found</h3><p>Try another product, company or date range. New rate changes will appear here.</p></div>@endforelse
    <div class="table-footer"><span>{{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} entries</span><nav class="pagination" aria-label="Rate log pages">@if($logs->previousPageUrl())<a class="page-button" href="{{ $logs->previousPageUrl() }}" aria-label="Previous page">‹</a>@endif<span>Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>@if($logs->nextPageUrl())<a class="page-button" href="{{ $logs->nextPageUrl() }}" aria-label="Next page">›</a>@endif</nav></div>
    </div>
</section>
<script src="{{ asset('js/rate-log-filters.js').'?v='.filemtime(public_path('js/rate-log-filters.js')) }}" defer></script>
</x-app-layout>
