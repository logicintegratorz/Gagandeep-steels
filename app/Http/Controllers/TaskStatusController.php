<?php

namespace App\Http\Controllers;

use App\Models\{LeadWorkItem, TaskStatus};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class TaskStatusController
{
    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['name'=>['required','string','max:80','unique:task_statuses,name'],'color'=>['required','regex:/^#[0-9a-fA-F]{6}$/']]);
        $data['position'] = (int) TaskStatus::max('position') + 1;
        TaskStatus::create($data);
        return back()->with('success', 'Task status added.');
    }

    public function update(Request $request, TaskStatus $taskStatus)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['name'=>['required','string','max:80',Rule::unique('task_statuses','name')->ignore($taskStatus)],'color'=>['required','regex:/^#[0-9a-fA-F]{6}$/']]);
        $old = $taskStatus->name;
        $taskStatus->update($data);
        if ($old !== $data['name']) LeadWorkItem::where('kind','Task')->where('status',$old)->update(['status'=>$data['name']]);
        return back()->with('success', 'Task status updated.');
    }

    public function destroy(Request $request, TaskStatus $taskStatus)
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_if(TaskStatus::count() <= 1, 422, 'At least one task status is required.');
        abort_if(LeadWorkItem::where('kind','Task')->where('status',$taskStatus->name)->exists(), 422, 'Move tasks out of this status before deleting it.');
        $taskStatus->delete();
        return back()->with('success', 'Task status deleted.');
    }
}
