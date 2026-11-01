<?php

namespace App\Http\Controllers;

use App\Models\SidebarGroup;
use App\Models\SidebarGroupItem;
use App\Models\SidebarItemOrder;
use App\Support\SidebarNavigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class SidebarGroupController
{
    public function index(Request $request)
    {
        $this->admin($request);
        $groups = SidebarGroup::with('items')->orderBy('position')->orderBy('id')->get();
        return view('sidebar-groups.index', [
            'groups' => $groups,
            'definitions' => SidebarNavigation::definitions(),
            'assigned' => $groups->flatMap->items->pluck('sidebar_group_id', 'item_key'),
        ]);
    }

    public function store(Request $request)
    {
        $this->admin($request);
        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            $group = SidebarGroup::create([
                'name' => $data['name'],
                'position' => (int) SidebarGroup::max('position') + 1,
            ]);
            $this->syncItems($group, $data['items'] ?? []);
        });
        return back()->with('success', 'Sidebar group created.');
    }

    public function update(Request $request, SidebarGroup $sidebarGroup)
    {
        $this->admin($request);
        $data = $this->validated($request, $sidebarGroup);
        DB::transaction(function () use ($sidebarGroup, $data) {
            $locked = SidebarGroup::lockForUpdate()->findOrFail($sidebarGroup->id);
            if ($locked->version !== (int) $data['version']) {
                throw ValidationException::withMessages(['name' => 'This group changed elsewhere. Refresh and try again.']);
            }
            $locked->name = $data['name'];
            $locked->version++;
            $locked->save();
            $this->syncItems($locked, $data['items'] ?? []);
        });
        return back()->with('success', 'Sidebar group updated.');
    }

    public function destroy(Request $request, SidebarGroup $sidebarGroup)
    {
        $this->admin($request);
        $data = $request->validate(['version' => ['required', 'integer']]);
        DB::transaction(function () use ($sidebarGroup, $data) {
            $locked = SidebarGroup::lockForUpdate()->findOrFail($sidebarGroup->id);
            if ($locked->version !== (int) $data['version']) {
                throw ValidationException::withMessages(['group' => 'This group changed elsewhere. Refresh and try again.']);
            }
            $locked->delete();
        });
        return back()->with('success', 'Sidebar group deleted. Its buttons are now unassigned.');
    }

    public function move(Request $request, string $itemKey)
    {
        $this->admin($request);
        $data = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);
        $keys = array_keys(SidebarNavigation::definitions());
        $index = array_search($itemKey, $keys, true);
        abort_if($index === false, 404);
        $target = $data['direction'] === 'up' ? $index - 1 : $index + 1;
        if (!isset($keys[$target])) return back()->with('status', 'This button is already at the edge.');
        [$keys[$index], $keys[$target]] = [$keys[$target], $keys[$index]];
        DB::transaction(function () use ($keys) {
            foreach ($keys as $position => $key) {
                SidebarItemOrder::updateOrCreate(['item_key' => $key], ['position' => $position + 1]);
                SidebarGroupItem::where('item_key', $key)->update(['position' => $position + 1]);
            }
        });
        return back()->with('success', 'Sidebar button moved.');
    }

    public function reorder(Request $request)
    {
        $this->admin($request);
        $validKeys = array_keys(SidebarNavigation::definitions());
        $data = $request->validate([
            'order' => ['required', 'array', 'size:'.count($validKeys)],
            'order.*' => ['required', 'string', 'distinct', Rule::in($validKeys)],
        ]);
        if (array_diff($validKeys, $data['order'])) throw ValidationException::withMessages(['order' => 'The complete sidebar order is required.']);
        DB::transaction(function () use ($data) {
            foreach ($data['order'] as $position => $key) {
                SidebarItemOrder::updateOrCreate(['item_key' => $key], ['position' => $position + 1]);
                SidebarGroupItem::where('item_key', $key)->update(['position' => $position + 1]);
            }
        });
        return response()->json(['saved' => true]);
    }

    private function validated(Request $request, ?SidebarGroup $group = null): array
    {
        $keys = array_keys(SidebarNavigation::definitions());
        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('sidebar_groups', 'name')->ignore($group?->id)],
            'items' => ['nullable', 'array'],
            'items.*' => ['string', 'distinct', Rule::in($keys)],
            'version' => [$group ? 'required' : 'nullable', 'integer'],
        ]);
    }

    private function syncItems(SidebarGroup $group, array $keys): void
    {
        $order = array_flip(array_keys(SidebarNavigation::definitions()));
        $group->items()->whereNotIn('item_key', $keys ?: [''])->delete();
        foreach ($keys as $key) {
            SidebarGroupItem::updateOrCreate(
                ['item_key' => $key],
                ['sidebar_group_id' => $group->id, 'position' => $order[$key] ?? 0]
            );
        }
    }

    private function admin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403);
    }
}
