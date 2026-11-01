<x-app-layout title="Sidebar groups">
    <link rel="stylesheet" href="{{ asset('css/content-manager.css').'?v='.filemtime(public_path('css/content-manager.css')) }}">
    <header class="page-heading">
        <div><span class="eyebrow">ADMINISTRATION</span><h1>Sidebar groups</h1><p>Create named groups and choose which navigation buttons appear inside each group.</p></div>
    </header>

    @if($errors->any())<div class="notice error" role="alert"><strong>Could not save the sidebar.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if(session('success'))<div class="notice success" role="status">{{ session('success') }}</div>@endif

    <section class="panel sidebar-group-create" aria-labelledby="new-sidebar-group">
        <div class="panel-heading"><div><h2 id="new-sidebar-group">Create group</h2><p>The group will be visible when it contains at least one button available to the signed-in user.</p></div></div>
        <form method="POST" action="{{ route('sidebar-groups.store') }}" class="sidebar-group-form">@csrf
            <label>Group name<input name="name" maxlength="80" required placeholder="Example: Sales workspace" value="{{ old('name') }}"></label>
            <fieldset><legend>Add buttons</legend><div class="sidebar-button-grid">
                @foreach($definitions as $key => $item)<label class="permission-check"><input type="checkbox" name="items[]" value="{{ $key }}" @checked(in_array($key, old('items', []), true))><span>{{ $item['label'] }}</span></label>@endforeach
            </div></fieldset>
            <div class="form-actions"><button class="button primary">Create group</button></div>
        </form>
    </section>

    <div class="sidebar-group-list">
        @forelse($groups as $group)
        <section class="panel sidebar-group-card" aria-labelledby="sidebar-group-{{ $group->id }}">
            <form method="POST" action="{{ route('sidebar-groups.update', $group) }}" class="sidebar-group-form">@csrf @method('PUT')<input type="hidden" name="version" value="{{ $group->version }}">
                <div class="sidebar-group-heading"><label>Group name<input id="sidebar-group-{{ $group->id }}" name="name" maxlength="80" required value="{{ old('name', $group->name) }}"></label><span>{{ $group->items->count() }} buttons</span></div>
                <fieldset><legend>Buttons in this group</legend><p>Selecting a button already used elsewhere will move it into this group.</p><div class="sidebar-button-grid">
                    @foreach($definitions as $key => $item)<label class="permission-check"><input type="checkbox" name="items[]" value="{{ $key }}" @checked($group->items->contains('item_key', $key))><span>{{ $item['label'] }}</span>@if(($assigned[$key] ?? null) && (int)$assigned[$key] !== $group->id)<small>In another group</small>@endif</label>@endforeach
                </div></fieldset>
                <div class="form-actions"><button class="button primary">Save changes</button></div>
            </form>
            <form method="POST" action="{{ route('sidebar-groups.destroy', $group) }}" class="sidebar-group-delete">@csrf @method('DELETE')<input type="hidden" name="version" value="{{ $group->version }}"><button class="button secondary">Delete group</button><small>Buttons will become unassigned; pages and permissions are not deleted.</small></form>
        </section>
        @empty
        <section class="panel empty-state"><h2>No custom groups yet</h2><p>Create the first group above. Until then, the sidebar keeps its current order.</p></section>
        @endforelse
    </div>
</x-app-layout>
