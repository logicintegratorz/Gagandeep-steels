<section class="workspace-global-bar" aria-label="Global workspace actions">
    <div class="workspace-global-actions">
        <button class="icon-button mobile-toggle" id="menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false" aria-controls="sidebar"><x-icon name="menu"/></button>
        <form class="workspace-global-search" method="GET" action="{{ route('global-search.index') }}" role="search" data-auto-global-search>
            <label class="sr-only" for="workspace-global-search-input">Search CRM</label>
            <x-icon name="search" size="18"/>
            <input id="workspace-global-search-input" type="search" name="q" maxlength="150" autocomplete="off" placeholder="Search name, number or product">
        </form>
        <nav class="workspace-global-shortcuts" aria-label="Quick actions">
            <button type="button" class="workspace-tool-button calculator-trigger" data-calculator-open aria-label="Open calculator" title="Calculator"><x-icon name="calculator" size="20"/></button>
            <button type="button" class="workspace-tool-button theme-toggle" data-theme-toggle aria-label="Use dark theme" title="Switch theme"><span data-theme-icon="dark"><x-icon name="moon" size="20"/></span><span data-theme-icon="light" hidden><x-icon name="sun" size="20"/></span></button>
            @if(auth()->user()->allows('products.view'))
                <a href="{{ route('products.pricing') }}"><x-icon name="rupee"/><span>Rates</span></a>
            @endif
            @if(auth()->user()->isAdmin())
                <button type="button" disabled title="Quotation workspace is coming soon"><x-icon name="document"/><span>Quotation</span></button>
                <button type="button" disabled title="SMS workspace is coming soon"><x-icon name="chat"/><span>SMS</span></button>
                <button type="button" disabled title="WhatsApp workspace is coming soon"><x-icon name="chat"/><span>WhatsApp</span></button>
                <button type="button" disabled title="Email workspace is coming soon"><x-icon name="mail"/><span>Email</span></button>
                <button type="button" disabled title="Call workspace is coming soon"><x-icon name="phone"/><span>Call</span></button>
            @endif
        </nav>
        @php
            $workspaceAccountParts = preg_split('/\s+/', trim((string) auth()->user()->name), -1, PREG_SPLIT_NO_EMPTY);
            $workspaceAccountInitials = mb_strtoupper(mb_substr($workspaceAccountParts[0] ?? 'U', 0, 1) . (count($workspaceAccountParts) > 1 ? mb_substr($workspaceAccountParts[array_key_last($workspaceAccountParts)], 0, 1) : ''));
        @endphp
        <div class="workspace-global-account">
            <x-notification-bell/>
            <span class="workspace-global-avatar" aria-label="{{ auth()->user()->name }}">
                @if(auth()->user()->avatar_url)
                    <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}">
                @else
                    {{ $workspaceAccountInitials }}
                @endif
            </span>
        </div>
    </div>
    <div class="workspace-global-welcome">
        <span><x-icon name="bell" size="15"/> Hi, {{ auth()->user()->name }}</span>
        <p>Welcome to Gagandeep Steels. Your workday, all in one place.</p>
        <button type="button" class="workspace-view-messages" data-dashboard-messages-open aria-haspopup="dialog">View messages <span aria-hidden="true">↗</span></button>
    </div>
</section>
