<x-app-layout title="Team chat">
    <link rel="stylesheet" href="{{ asset('css/chat.css').'?v='.filemtime(public_path('css/chat.css')) }}">
    @php
        $conversation = $selectedGroup ?? $selectedUser;
        $conversationName = $selectedGroup?->name ?? $selectedUser?->name;
        $dateLabel = function ($date) {
            $day = $date->timezone('Asia/Kolkata')->startOfDay();
            if ($day->isToday()) return 'Today';
            if ($day->isYesterday()) return 'Yesterday';
            return $day->format('d M Y');
        };
    @endphp

    <section class="panel chat-page" aria-label="Team chat">
        <header class="chat-page-heading">
            <div><h1>Team chat</h1><p>Permanent direct and group conversations for active CRM users.</p></div>
            @if($totalUnread)<span class="chat-unread-total">{{ $totalUnread }} unread</span>@endif
        </header>

        <div class="chat-layout">
            <aside class="chat-people" aria-label="Conversations">
                <div class="chat-list-heading"><h2>Direct messages</h2><button type="button" class="chat-create-group" data-chat-group-open>+ Group</button></div>
                @forelse($people as $person)
                    <a class="chat-person {{ $selectedUser?->id === $person->id ? 'is-selected' : '' }}" href="{{ route('chat.index', ['user' => $person->id]) }}">
                        <span class="chat-person-avatar">{{ mb_strtoupper(mb_substr($person->name, 0, 1)) }}</span>
                        <span><strong>{{ $person->name }}</strong><small>{{ $person->role?->name ?? 'User' }}</small></span>
                        @if($unreadBySender->get($person->id))<b>{{ $unreadBySender->get($person->id) }}</b>@endif
                    </a>
                @empty
                    <p class="chat-empty">No other active users are available.</p>
                @endforelse

                <div class="chat-list-heading chat-group-heading"><h2>Groups</h2></div>
                @forelse($groups as $group)
                    <a class="chat-person chat-group {{ $selectedGroup?->id === $group->id ? 'is-selected' : '' }}" href="{{ route('chat.index', ['group' => $group->id]) }}">
                        <span class="chat-person-avatar"><x-icon name="users" size="17"/></span>
                        <span><strong>{{ $group->name }}</strong><small>{{ $group->members_count }} members</small></span>
                    </a>
                @empty
                    <p class="chat-empty chat-empty-groups">No groups yet.</p>
                @endforelse
            </aside>

            <div class="chat-conversation">
                @if($conversation)
                    <header class="chat-conversation-heading">
                        <span class="chat-person-avatar">@if($selectedGroup)<x-icon name="users" size="18"/>@else{{ mb_strtoupper(mb_substr($selectedUser->name, 0, 1)) }}@endif</span>
                        <div><h2>{{ $conversationName }}</h2><p>{{ $selectedGroup ? $selectedGroup->members_count.' members' : ($selectedUser->role?->name ?? 'User') }}</p></div>
                    </header>

                    <div class="chat-messages" aria-live="polite">
                        @forelse($messages->groupBy(fn ($message) => $message->created_at->timezone('Asia/Kolkata')->toDateString()) as $dayMessages)
                            <div class="chat-date-divider"><span>{{ $dateLabel($dayMessages->first()->created_at) }}</span></div>
                            @foreach($dayMessages as $message)
                                <article class="chat-message {{ $message->sender_id === auth()->id() ? 'is-mine' : '' }}">
                                    @if($selectedGroup && $message->sender_id !== auth()->id())<strong class="chat-message-sender">{{ $message->sender?->name ?? 'Former user' }}</strong>@endif
                                    <p>{{ $message->body }}</p>
                                    <div class="chat-message-meta">
                                        <time datetime="{{ $message->created_at->toIso8601String() }}">
                                            {{ $message->created_at->timezone('Asia/Kolkata')->format('h:i A') }}
                                            @if($message->edited_at)
                                                · edited
                                            @endif
                                        </time>

                                        @if($message->sender_id === auth()->id())
                                            <details class="chat-message-menu">
                                                <summary aria-label="Message actions">•••</summary>
                                                <div>
                                                    <form method="POST" action="{{ $selectedGroup ? route('chat.group-messages.update', $message) : route('chat.messages.update', $message) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <label>
                                                            Edit message
                                                            <textarea name="body" rows="2" maxlength="4000" required>{{ $message->body }}</textarea>
                                                        </label>
                                                        <button class="button primary">Save</button>
                                                    </form>

                                                    <form method="POST" action="{{ $selectedGroup ? route('chat.group-messages.destroy', $message) : route('chat.messages.destroy', $message) }}" onsubmit="return confirm('Delete this message?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="chat-message-delete">Delete</button>
                                                    </form>
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        @empty
                            <div class="chat-empty"><x-icon name="chat" size="30"/><p>Start a conversation with {{ $conversationName }}.</p></div>
                        @endforelse
                    </div>

                    <form class="chat-composer" method="POST" action="{{ $selectedGroup ? route('chat.groups.messages.store', $selectedGroup) : route('chat.store', $selectedUser) }}">
                        @csrf
                        <label class="sr-only" for="chat-message">Message {{ $conversationName }}</label>
                        <textarea id="chat-message" name="body" rows="2" maxlength="4000" placeholder="Write a message…" required autofocus>{{ old('body') }}</textarea>
                        <button class="button primary">Send</button>
                    </form>
                @else
                    <div class="chat-empty chat-empty-full"><x-icon name="chat" size="36"/><h2>No conversation available</h2><p>Create or activate another user account to start a chat.</p></div>
                @endif
            </div>
        </div>
    </section>

    <dialog class="chat-group-dialog" data-chat-group-dialog aria-labelledby="chat-group-title">
        <form method="POST" action="{{ route('chat.groups.store') }}">
            @csrf
            <header><div><h2 id="chat-group-title">Create group</h2><p>Select active users who can view and send messages in this group.</p></div><button type="button" data-chat-group-close aria-label="Close">×</button></header>
            <label>Group name<input name="name" maxlength="120" required value="{{ old('name') }}" placeholder="e.g. Sales follow-up"></label>
            <fieldset><legend>Members</legend>@foreach($people as $person)<label class="chat-member-option"><input type="checkbox" name="member_ids[]" value="{{ $person->id }}" @checked(in_array($person->id, old('member_ids', [])))><span>{{ $person->name }}<small>{{ $person->role?->name ?? 'User' }}</small></span></label>@endforeach</fieldset>
            <div class="chat-group-actions"><button type="button" class="button secondary" data-chat-group-close>Cancel</button><button class="button primary">Create group</button></div>
        </form>
    </dialog>
    <script src="{{ asset('js/chat.js').'?v='.filemtime(public_path('js/chat.js')) }}" defer></script>
</x-app-layout>
