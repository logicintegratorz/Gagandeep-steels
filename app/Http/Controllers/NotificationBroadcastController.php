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
    public function compose()
    {
        return view('notifications.compose', [
            'roles'=>Role::withCount(['users'=>fn($q)=>$q->where('active',true)])->orderBy('name')->get(),
            'recipients'=>User::with('role')->where('active',true)->orderBy('name')->get(),
        ]);
    }
    public function store(Request $request)
    {
        foreach (['title','message'] as $field) if (is_string($request->input($field))) $request->merge([$field=>trim($request->input($field))]);
        $data = $request->validate([
            'submission_key'=>['required','uuid'], 'title'=>['nullable','string','max:120'], 'message'=>['required','string','max:2000'],
            'audience'=>['required',Rule::in(['all','roles','users'])],
            'role_ids'=>['exclude_unless:audience,roles','required','array','min:1','max:500'],
            'role_ids.*'=>['integer','distinct','exists:roles,id'],
            'user_ids'=>['exclude_unless:audience,users','required','array','min:1','max:500'],
            'user_ids.*'=>['integer','distinct','exists:users,id'],
        ]);
        $data['title'] = filled($data['title'] ?? null) ? $data['title'] : 'Notification';
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
        return redirect()->route($request->user()->allows('notifications.view') ? 'broadcasts.index' : 'notifications.index')->with('success','Notification sent to '.$count.' active '.($count===1?'user.':'users.'));
    }
    private function alreadySent(Request $request, NotificationBroadcast $broadcast)
    {
        abort_unless($broadcast->sender_id===$request->user()->id,403);
        return redirect()->route('notifications.index')->with('status','This notification was already sent. No duplicate was created.');
    }
    public function index(Request $request)
    {
        $query=NotificationBroadcast::with('sender')->withCount(['deliveries as read_count'=>fn($q)=>$q->whereNotNull('read_at')]);
        if (!$request->user()->isAdmin()) $query->where('sender_id',$request->user()->id);
        return view('notifications.sent',['broadcasts'=>$query->orderByDesc('id')->paginate(15)]);
    }
    public function recall(Request $request, NotificationBroadcast $broadcast)
    {
        abort_unless($request->user()->isAdmin() || $broadcast->sender_id===$request->user()->id,403);
        NotificationBroadcast::whereKey($broadcast->id)->whereNull('recalled_at')->update(['recalled_at'=>now()]);
        return back()->with('success','Notification recalled. It is no longer visible in recipients’ inboxes.');
    }
}
