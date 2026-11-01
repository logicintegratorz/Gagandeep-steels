<?php
namespace App\Http\Controllers;

use App\Models\CrmNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class NotificationController
{
    public function index(Request $request)
    {
        $data = $request->validate(['status' => ['nullable', Rule::in(['all', 'unread', 'read'])]]);
        $query = CrmNotification::visibleTo($request->user());
        $unread = (clone $query)->whereNull('read_at')->count();
        $status = $data['status'] ?? 'all';
        if ($status === 'unread') $query->whereNull('read_at');
        if ($status === 'read') $query->whereNotNull('read_at');
        return view('notifications.index', ['notifications' => $query->orderByDesc('id')->paginate(15)->withQueryString(), 'unread' => $unread, 'status' => $status, 'soundEnabled'=>(bool)\Illuminate\Support\Facades\DB::table('notification_settings')->where('id',1)->value('sound_enabled')]);
    }
    public function count(Request $request)
    {
        if ($request->user()->allows('leads.view')) \App\Support\LeadReminders::deliver($request->user()->id);
        return response()->json(['unread' => CrmNotification::visibleTo($request->user())->whereNull('read_at')->count(), 'latest_message'=>(int)CrmNotification::visibleTo($request->user())->whereNull('read_at')->whereNotNull('broadcast_id')->max('id'), 'sound_enabled'=>(bool)\Illuminate\Support\Facades\DB::table('notification_settings')->where('id',1)->value('sound_enabled')])->header('Cache-Control', 'no-store');
    }
    public function sound(Request $request) {
        abort_unless($request->user()->isAdmin(),403);
        $data=$request->validate(['enabled'=>['required','boolean']]);
        \Illuminate\Support\Facades\DB::table('notification_settings')->where('id',1)->update(['sound_enabled'=>$data['enabled']]);
        return back()->with('success',$data['enabled'] ? 'Notification sound enabled for everyone.' : 'Notification sound disabled for everyone.');
    }
    public function read(Request $request, int $notification)
    {
        $entry = CrmNotification::visibleTo($request->user())->findOrFail($notification);
        if (!$entry->read_at) $entry->update(['read_at' => now()]);
        return back()->with('success', 'Notification marked as read.');
    }
    public function readAll(Request $request)
    {
        $count = CrmNotification::visibleTo($request->user())->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', $count ? $count.' notifications marked as read.' : 'You have no unread notifications.');
    }
    public function destroy(Request $request, int $notification)
    {
        $entry = CrmNotification::visibleTo($request->user())->findOrFail($notification);
        $entry->update(['dismissed_at' => now(), 'read_at' => $entry->read_at ?? now()]);
        return back()->with('success', 'Notification dismissed.');
    }
}
