<x-app-layout title="Storage overview">
    <link rel="stylesheet" href="{{ asset('css/storage.css').'?v='.filemtime(public_path('css/storage.css')) }}">
    @php
        $formatBytes = function (int|float $bytes): string {
            if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1).' KB';
            return number_format($bytes / 1024 / 1024, 2).' MB';
        };
    @endphp

    <section class="storage-page">
        <header class="storage-banner">
            <span class="storage-banner-icon"><x-icon name="box" size="24"/></span>
            <div><h1>Storage overview</h1><p>Track how your CRM workspace uses storage.</p></div>
        </header>

        <section class="storage-summary" aria-label="Storage summary">
            <div class="storage-gauge" aria-label="{{ rtrim(rtrim(number_format($percent, 1), '0'), '.') }} percent of storage used">
                <svg class="storage-gauge-chart" viewBox="0 0 180 100" aria-hidden="true" focusable="false">
                    <path class="storage-gauge-track" pathLength="100" d="M20 86 A70 70 0 0 1 160 86"/>
                    <path class="storage-gauge-value" pathLength="100" stroke-dasharray="{{ $percent }} 100" d="M20 86 A70 70 0 0 1 160 86"/>
                </svg>
                <div class="storage-gauge-copy"><strong>{{ rtrim(rtrim(number_format($percent, 1), '0'), '.') }}%</strong><span>used</span></div>
            </div>
            <a class="storage-stat" href="#storage-modules"><x-icon name="box" size="20"/><div><strong>{{ $formatBytes($total) }}</strong><span>Total storage</span></div></a>
            <a class="storage-stat" href="#storage-modules"><x-icon name="document" size="20"/><div><strong>{{ $formatBytes($used) }}</strong><span>Used storage</span></div></a>
            <a class="storage-stat is-free" href="#storage-modules"><x-icon name="download" size="20"/><div><strong>{{ $formatBytes($total - $used) }}</strong><span>Free storage</span></div></a>
            <a class="storage-stat" href="#storage-modules"><x-icon name="check" size="20"/><div><strong>{{ count($modules) }}</strong><span>Active modules</span></div></a>
        </section>

        <section class="storage-modules" id="storage-modules" aria-label="CRM modules">
            @foreach($modules as $module)
                <a class="storage-module" href="{{ $module['url'] }}" title="Open {{ $module['label'] }}">
                    <span class="storage-module-icon"><x-icon :name="$module['icon']" size="20"/></span>
                    <div><h2>{{ $module['label'] }}</h2><p>{{ number_format($module['count']) }} {{ $module['count'] === 1 ? 'record' : 'records' }}@if(isset($module['bytes'])) · {{ $formatBytes($module['bytes']) }}@endif</p></div>
                </a>
            @endforeach
        </section>

        <p class="storage-note">Storage usage includes CRM files saved in this workspace. Record counts are shown separately for each active module.</p>
    </section>
</x-app-layout>
