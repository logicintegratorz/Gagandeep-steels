<?php

namespace App\Http\Controllers;

use App\Models\ChatGroup;
use App\Models\ChatGroupMessage;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ChatController
{
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $people = User::query()
            ->with('role')
            ->where('active', true)
            ->where('id', '!=', $currentUser->id)
            ->orderBy('name')
            ->get();

        $groups = ChatGroup::query()
            ->whereHas('members', fn ($members) => $members->whereKey($currentUser->id))
            ->withCount('members')
            ->orderBy('name')
            ->get();
        $selectedGroup = $groups->firstWhere('id', $request->integer('group'));
        $selectedUser = $selectedGroup ? null : ($people->firstWhere('id', $request->integer('user')) ?? $people->first());
        $messages = collect();

        if ($selectedUser) {
            DirectMessage::query()
                ->where('sender_id', $selectedUser->id)
                ->where('recipient_id', $currentUser->id)
                ->whereNull('read_at')
                ->update(['read_at' => now(), 'updated_at' => now()]);

            $messages = DirectMessage::betweenUsers($currentUser->id, $selectedUser->id)
                ->with('sender:id,name')
                ->latest('id')
                ->limit(100)
                ->get()
                ->reverse()
                ->values();
        }
        if ($selectedGroup) {
            $messages = $selectedGroup->messages()
                ->with('sender:id,name')
                ->latest('id')
                ->limit(100)
                ->get()
                ->reverse()
                ->values();
        }

        $unreadBySender = DirectMessage::query()
            ->selectRaw('sender_id, count(*) as unread_count')
            ->where('recipient_id', $currentUser->id)
            ->whereNull('read_at')
            ->groupBy('sender_id')
            ->pluck('unread_count', 'sender_id');

        return view('chat.index', [
            'people' => $people,
            'selectedUser' => $selectedUser,
            'messages' => $messages,
            'unreadBySender' => $unreadBySender,
            'totalUnread' => $unreadBySender->sum(),
            'groups' => $groups,
            'selectedGroup' => $selectedGroup,
        ]);
    }

    public function store(Request $request, User $user)
    {
        abort_unless($user->active && $user->id !== $request->user()->id, 404);
        $data = $request->validate(['body' => ['required', 'string', 'max:4000']]);

        DirectMessage::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $user->id,
            'body' => trim($data['body']),
        ]);

        return redirect()->route('chat.index', ['user' => $user->id])->with('success', 'Message sent.');
    }

    public function createGroup(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'member_ids' => ['required', 'array', 'min:1', 'max:100'],
            'member_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ]);
        $requestedMemberIds = collect($data['member_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $activeMemberIds = User::query()
            ->where('active', true)
            ->whereIn('id', $requestedMemberIds)
            ->pluck('id');
        abort_unless($activeMemberIds->count() === $requestedMemberIds->count(), 422);
        $memberIds = $activeMemberIds
            ->push($request->user()->id)
            ->unique()
            ->values();

        $group = DB::transaction(function () use ($request, $data, $memberIds) {
            $group = ChatGroup::create(['name' => trim($data['name']), 'created_by' => $request->user()->id]);
            $group->members()->sync($memberIds->all());
            return $group;
        });

        return redirect()->route('chat.index', ['group' => $group->id])->with('success', 'Group created.');
    }

    public function storeGroupMessage(Request $request, ChatGroup $group)
    {
        abort_unless($group->members()->whereKey($request->user()->id)->exists(), 404);
        $data = $request->validate(['body' => ['required', 'string', 'max:4000']]);
        ChatGroupMessage::create(['chat_group_id' => $group->id, 'sender_id' => $request->user()->id, 'body' => trim($data['body'])]);
        return redirect()->route('chat.index', ['group' => $group->id])->with('success', 'Message sent.');
    }

    public function updateDirectMessage(Request $request, DirectMessage $message)
    {
        abort_unless($message->sender_id === $request->user()->id, 403);
        $data = $request->validate(['body' => ['required','string','max:4000']]);
        $message->update(['body' => trim($data['body']), 'edited_at' => now()]);
        return redirect()->route('chat.index', ['user' => $message->recipient_id])->with('success', 'Message updated.');
    }

    public function destroyDirectMessage(Request $request, DirectMessage $message)
    {
        abort_unless($message->sender_id === $request->user()->id, 403);
        $recipientId = $message->recipient_id; $message->delete();
        return redirect()->route('chat.index', ['user' => $recipientId])->with('success', 'Message deleted.');
    }

    public function updateGroupMessage(Request $request, ChatGroupMessage $message)
    {
        abort_unless($message->sender_id === $request->user()->id && $message->group->members()->whereKey($request->user()->id)->exists(), 403);
        $data = $request->validate(['body' => ['required','string','max:4000']]);
        $message->update(['body' => trim($data['body']), 'edited_at' => now()]);
        return redirect()->route('chat.index', ['group' => $message->chat_group_id])->with('success', 'Message updated.');
    }

    public function destroyGroupMessage(Request $request, ChatGroupMessage $message)
    {
        abort_unless($message->sender_id === $request->user()->id && $message->group->members()->whereKey($request->user()->id)->exists(), 403);
        $groupId = $message->chat_group_id; $message->delete();
        return redirect()->route('chat.index', ['group' => $groupId])->with('success', 'Message deleted.');
    }
}
