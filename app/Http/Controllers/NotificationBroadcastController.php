<?php
namespace App\Http\Controllers;

use App\Models\{CrmNotification, NotificationBroadcast, Role, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

final class NotificationBroadcastController
{
    private function requireAdministrator(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    public function compose(Request $request)
    {
        $this->requireAdministrator($request);
        return view('notifications.compose', [
            'roles'=>Role::withCount(['users'=>fn($q)=>$q->where('active',true)])->orderBy('name')->get(),
            'recipients'=>User::with('role')->where('active',true)->orderBy('name')->get(),
        ]);
    }
    public function store(Request $request)
    {
        $this->requireAdministrator($request);
        foreach (['title','message'] as $field) if (is_string($request->input($field))) $request->merge([$field=>trim($request->input($field))]);
        $data = $request->validate([
            'submission_key'=>['required','uuid'], 'title'=>['nullable','string','max:120'], 'message'=>['required','string','max:2000'],
            'audience'=>['required',Rule::in(['all','roles','users'])],
            'recipient_id'=>['nullable','integer','exists:users,id'],
            'role_ids'=>['exclude_unless:audience,roles','required','array','min:1','max:500'],
            'role_ids.*'=>['integer','distinct','exists:roles,id'],
            'user_ids'=>['exclude_unless:audience,users','required','array','min:1','max:500'],
            'user_ids.*'=>['integer','distinct','exists:users,id'],
        ]);
        if (!empty($data['recipient_id'])) {
            $data['audience'] = 'users';
            $data['user_ids'] = [(int) $data['recipient_id']];
        }
        $data['title'] = $data['title'] ?? '';
        if ($existing = NotificationBroadcast::where('submission_key',$data['submission_key'])->first()) return $this->alreadySent($request,$existing);
        try {
            $count = DB::transaction(function () use ($request,$data) {
                $query = User::where('active',true)->orderBy('id');
                if ($data['audience']==='roles') $query->whereIn('role_id',$data['role_ids']);
                if ($data['audience']==='users') $query->whereIn('id',$data['user_ids']);
                $ids = $query->sharedLock()->pluck('id');
                if ($ids->isEmpty()) throw ValidationException::withMessages(['audience'=>'No active users match the selected recipients.']);
                $broadcast = NotificationBroadcast::create([
                    'sender_id'=>$request->user()->id,'submission_key'=>$data['submission_key'],'title'=>$data['title'],
                    'message'=>$data['message'],'audience'=>$data['audience'],'recipient_count'=>$ids->count(),
                ]);
                foreach ($ids->chunk(300) as $chunk) {
                    CrmNotification::insert($chunk->map(fn($id)=>[
                        'user_id'=>$id,'broadcast_id'=>$broadcast->id,'title'=>$data['title'],
                        'message'=>'From '.$request->user()->name.': '.$data['message'],'permission'=>null,
                        'read_at'=>null,'created_at'=>now(),'updated_at'=>now(),
                    ])->all());
                }
                return $ids->count();
            });
        } catch (UniqueConstraintViolationException $exception) {
            $existing = NotificationBroadcast::where('submission_key',$data['submission_key'])->first();
            if (!$existing) throw $exception;
            return $this->alreadySent($request,$existing);
        }
        return back()->with('success','Message sent to '.$count.' active '.($count===1?'user.':'users.'));
    }
    private function alreadySent(Request $request, NotificationBroadcast $broadcast)
    {
        abort_unless($broadcast->sender_id===$request->user()->id,403);
        return redirect()->route('notifications.index')->with('status','This notification was already sent. No duplicate was created.');
    }
    public function index(Request $request)
    {
        $this->requireAdministrator($request);
        $query=NotificationBroadcast::with('sender')->withCount(['deliveries as read_count'=>fn($q)=>$q->whereNotNull('read_at')]);
        return view('notifications.sent',['broadcasts'=>$query->orderByDesc('id')->paginate(15)]);
    }
    public function recall(Request $request, NotificationBroadcast $broadcast)
    {
        $this->requireAdministrator($request);
        NotificationBroadcast::whereKey($broadcast->id)->whereNull('recalled_at')->update(['recalled_at'=>now()]);
        return back()->with('success','Notification recalled. It is no longer visible in recipients’ inboxes.');
    }
    public function update(Request $request, NotificationBroadcast $broadcast)
    {
        $this->requireAdministrator($request);
        foreach (['title','message'] as $field) if (is_string($request->input($field))) $request->merge([$field => trim($request->input($field))]);
        $data = $request->validate(['title'=>['nullable','string','max:120'], 'message'=>['required','string','max:2000']]);
        $data['title'] = $data['title'] ?? '';
        DB::transaction(function () use ($broadcast, $data) {
            $broadcast->update($data);
            CrmNotification::where('broadcast_id', $broadcast->id)->update([
                'title'=>$data['title'], 'message'=>'From '.($broadcast->sender?->name ?: 'Administrator').': '.$data['message'], 'updated_at'=>now(),
            ]);
        });
        return back()->with('success', 'Sent message updated for all recipients.');
    }
    public function destroy(Request $request, NotificationBroadcast $broadcast)
    {
        $this->requireAdministrator($request);
        DB::transaction(function () use ($broadcast) {
            CrmNotification::where('broadcast_id', $broadcast->id)->delete();
            $broadcast->delete();
        });
        return back()->with('success', 'Sent message and its delivery records were deleted.');
    }
    public function resend(Request $request, NotificationBroadcast $broadcast)
    {
        $this->requireAdministrator($request);
        $ids = User::where('active', true)->whereIn('id', $broadcast->deliveries()->pluck('user_id'))->orderBy('id')->pluck('id');
        if ($ids->isEmpty()) return back()->withErrors(['message'=>'This message has no active original recipients to resend to.']);

        DB::transaction(function () use ($request, $broadcast, $ids) {
            $resent = NotificationBroadcast::create([
                'sender_id'=>$request->user()->id, 'submission_key'=>(string) \Illuminate\Support\Str::uuid(),
                'title'=>$broadcast->title, 'message'=>$broadcast->message, 'audience'=>'users', 'recipient_count'=>$ids->count(),
            ]);
            foreach ($ids->chunk(300) as $chunk) {
                CrmNotification::insert($chunk->map(fn($id)=>[
                    'user_id'=>$id, 'broadcast_id'=>$resent->id, 'title'=>$resent->title,
                    'message'=>'From '.$request->user()->name.': '.$resent->message, 'permission'=>null,
                    'read_at'=>null, 'created_at'=>now(), 'updated_at'=>now(),
                ])->all());
            }
        });
        return back()->with('success', 'Message resent to '.$ids->count().' active '.($ids->count() === 1 ? 'user.' : 'users.'));
    }
}
