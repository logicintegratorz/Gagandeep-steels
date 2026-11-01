<x-session/>
<aside class="sidebar" id="sidebar" aria-label="Workspace navigation">
    <button type="button" class="sidebar-close icon-button" aria-label="Close navigation">×</button>
    <a class="brand" href="{{ route('home') }}"><img class="brand-logo" src="{{ asset('images/gagandeep-steels-logo.png') }}" alt="Gagandeep Steels — Iron &amp; Steel Suppliers" width="267" height="81"></a>
    @php
        $sidebarItems = \App\Support\SidebarNavigation::visible(auth()->user());
        $sidebarGroups = \App\Models\SidebarGroup::with('items')->orderBy('position')->orderBy('id')->get();
        $assignedSidebarKeys = $sidebarGroups->flatMap->items->pluck('item_key')->all();
        $unassignedSidebarItems = collect($sidebarItems)->except($assignedSidebarKeys);
        $chatUnread = \App\Models\DirectMessage::where('recipient_id', auth()->id())->whereNull('read_at')->count();
    @endphp
    <nav>
        @foreach($unassignedSidebarItems as $item)@include('components.sidebar-link', ['item' => $item, 'chatUnread' => $chatUnread])@endforeach
        @foreach($sidebarGroups as $sidebarGroup)
            @php($groupSidebarItems = $sidebarGroup->items->map(fn($entry) => $sidebarItems[$entry->item_key] ?? null)->filter())
            @if($groupSidebarItems->isNotEmpty())
                <details class="sidebar-nav-group" @if($groupSidebarItems->contains(fn($item) => $item['active'])) open @endif>
                    <summary><span>{{ $sidebarGroup->name }}</span><x-icon name="chevron" size="14"/></summary>
                    <div class="sidebar-group-items">@foreach($groupSidebarItems as $item)@include('components.sidebar-link', ['item' => $item, 'chatUnread' => $chatUnread])@endforeach</div>
                </details>
            @endif
        @endforeach
        @if(auth()->user()->isAdmin())
            <div class="sidebar-nav-group sidebar-admin-group"><p class="sidebar-group-label">Admin</p><a class="{{ request()->routeIs('sidebar-groups.*') ? 'nav-active' : 'nav-link' }}" href="{{ route('sidebar-groups.index') }}"><x-icon name="menu"/><span>Sidebar groups</span></a></div>
        @endif
    </nav>
    <div class="profile"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><div>{{ auth()->user()->name }}<small>{{ auth()->user()->role->name }}</small></div></div>
    <form method="POST" action="{{ route('logout') }}" class="logout-form">@csrf<button type="submit" class="button secondary">Sign out</button></form>
</aside>
<button type="button" class="nav-backdrop" aria-label="Close navigation" tabindex="-1" hidden></button>
