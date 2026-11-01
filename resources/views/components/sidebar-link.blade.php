<div class="sidebar-sortable-item" data-sidebar-key="{{ $item['key'] }}" @if(auth()->user()->isAdmin()) draggable="true" @endif>
    <a class="{{ $item['active'] ? 'nav-active' : 'nav-link' }}" href="{{ $item['url'] }}" @if($item['active']) aria-current="page" @endif>
        <x-icon name="{{ $item['icon'] }}"/><span>{{ $item['label'] }}</span>
        @if($item['key'] === 'chat' && $chatUnread)<small class="sidebar-chat-count">{{ $chatUnread > 99 ? '99+' : $chatUnread }}</small>@endif
        @if(in_array($item['key'], ['rates','transporters','access'], true))<x-icon name="chevron" size="15"/>@endif
    </a>
    @if(auth()->user()->isAdmin())
    <div class="sidebar-sort-controls" aria-label="Reorder {{ $item['label'] }}">
        <button type="button" class="sidebar-drag-handle" data-sidebar-drag-handle title="Drag {{ $item['label'] }}" aria-label="Drag {{ $item['label'] }} to a new position">↕</button>
    </div>
    @endif
</div>
