<?php

namespace App\Http\Controllers;

use App\Models\ProductivityItem;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

final class ProductivityController
{
    private const MAP = ['reminders'=>'Reminder','meetings'=>'Meeting','to-dos'=>'To-Do','notes'=>'Note'];

    public function index(Request $request, string $module)
    {
        $type = self::MAP[$module] ?? abort(404);
        $q = trim((string)$request->query('q'));
        $items = ProductivityItem::visibleTo($request->user())->where('module',$type)
            ->with(['creator','assignee','assignees'])->when($q, fn($query)=>$query->where(fn($x)=>$x->where('title','like',"%{$q}%")->orWhere('message','like',"%{$q}%")))
            ->latest('scheduled_at')->paginate(25)->withQueryString();
        $statuses = TaskStatus::orderBy('position')->get(['name','color']);
        return view('productivity.index', compact('items','type','module','q','statuses') + ['users'=>User::where('active',true)->orderBy('name')->get(['id','name'])]);
    }

    public function store(Request $request, string $module)
    {
        $type = self::MAP[$module] ?? abort(404);
        $rules = ['title'=>['required','string','max:255'],'message'=>['nullable','string','max:10000'],'scheduled_at'=>['nullable','date'],'assigned_to'=>['nullable','array','max:20'],'assigned_to.*'=>['nullable','exists:users,id'],'recurrence'=>['nullable',Rule::in(['Once','Daily','Weekly','Monthly','Quarterly','Half-Yearly','Yearly'])],'priority'=>['nullable',Rule::in(['Low','Normal','High'])],'template_type'=>['nullable',Rule::in(['WhatsApp','Meta'])],'template_name'=>['nullable','string','max:255'],'customer_emails'=>['required_if:email_automation,1','nullable','array','max:5'],'customer_emails.0'=>['required_if:email_automation,1','nullable','email','max:120'],'customer_emails.*'=>['nullable','email','max:120'],'customer_mobiles'=>['required_if:whatsapp_automation,1','nullable','array','max:5'],'customer_mobiles.0'=>['required_if:whatsapp_automation,1','nullable','string','max:30'],'customer_mobiles.*'=>['nullable','string','max:30'],'attachment'=>['nullable','file','max:5120','mimes:jpg,jpeg,png,gif,webp,mp4,mov,pdf,doc,docx,xls,xlsx,ppt,pptx,txt']];
        $data = $request->validate($rules);
        foreach (['carry_forward','send_instant','email_automation','whatsapp_automation'] as $flag) $data[$flag] = $request->boolean($flag);
        $data['customer_email'] = collect($data['customer_emails'] ?? [])->filter()->implode(', ');
        $data['customer_mobile'] = collect($data['customer_mobiles'] ?? [])->filter()->implode(', ');
        unset($data['customer_emails'], $data['customer_mobiles']);
        $assigneeIds = collect($data['assigned_to'] ?? [])->filter()->unique()->values()->all();
        $data['assigned_to'] = $assigneeIds[0] ?? null;
        $data['module']=$type; $data['created_by']=$request->user()->id; $data['scheduled_at']=$data['scheduled_at'] ?? now(); $data['recurrence']=$data['recurrence'] ?? 'Once';
        if ($file=$request->file('attachment')) { $data += ['attachment_name'=>$file->getClientOriginalName(),'attachment_path'=>$file->store('productivity-attachments'),'attachment_mime'=>$file->getClientMimeType(),'attachment_size'=>$file->getSize()]; }
        $item = ProductivityItem::create($data);
        $item->assignees()->sync($assigneeIds);
        return back()->with('success',"{$type} created.");
    }

    public function updateStatus(Request $request, ProductivityItem $item)
    {
        abort_unless($item->module === (self::MAP[$request->route('module')] ?? ''), 404);
        $this->authorizeItem($request, $item);
        $data = $request->validate(['status' => ['required', Rule::exists('task_statuses', 'name')]]);
        $item->update($data);
        return $request->expectsJson() ? response()->json(['status' => $item->status]) : back()->with('success', 'Status updated.');
    }

    public function destroy(Request $request, ProductivityItem $item)
    {
        $this->authorizeItem($request, $item);
        if ($item->attachment_path) Storage::delete($item->attachment_path);
        $item->delete(); return back()->with('success',"{$item->module} deleted.");
    }

    public function attachment(Request $request, ProductivityItem $item)
    {
        $this->authorizeItem($request, $item);
        abort_unless($item->attachment_path && Storage::exists($item->attachment_path),404);
        return Storage::download($item->attachment_path,$item->attachment_name);
    }

    private function authorizeItem(Request $request, ProductivityItem $item): void
    {
        abort_unless(
            $request->user()->isAdmin()
            || $item->created_by === $request->user()->id
            || $item->assigned_to === $request->user()->id
            || $item->assignees()->whereKey($request->user()->id)->exists(),
            403
        );
    }
}
