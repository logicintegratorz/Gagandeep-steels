<?php

namespace App\Http\Controllers;

use App\Models\CrmActivity;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ActivityController
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(CrmActivity::STATUSES)],
            'scope' => ['nullable', Rule::in(['all', 'my'])],
        ]);
        $scope = $filters['scope'] ?? 'all';
        $activities = CrmActivity::query()->visibleTo($request->user())->with(['lead', 'leads', 'creator', 'assignee'])
            ->when($scope === 'my', fn ($query) => $query->where(fn ($items) => $items->where('created_by', $request->user()->id)->orWhere('assigned_to', $request->user()->id)))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($items) => $items->where('name', 'like', '%'.$q.'%')->orWhereHas('lead', fn ($lead) => $lead->where('name', 'like', '%'.$q.'%')->orWhere('company', 'like', '%'.$q.'%'))))
            ->orderByRaw("status = 'Complete'")->orderBy('scheduled_at')->paginate(30)->withQueryString();

        return view('activities.index', [
            'activities' => $activities,
            'users' => User::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'leads' => Lead::visibleTo($request->user())->orderBy('name')->limit(300)->get(['id', 'name', 'company']),
            'filters' => ['q' => $filters['q'] ?? '', 'status' => $filters['status'] ?? '', 'scope' => $scope],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $leadIds = $data['lead_ids'];
        unset($data['lead_ids']);
        $activity = CrmActivity::create($data + ['created_by' => $request->user()->id]);
        $this->syncLeads($activity, $leadIds);
        return redirect()->route('activities.index', $request->boolean('create_another') ? ['new' => 1] : [])->with('success', $activity->activity_code.' created.');
    }

    public function update(Request $request, CrmActivity $activity)
    {
        $this->authorizeActivity($request, $activity);
        $data = $this->data($request);
        $leadIds = $data['lead_ids'];
        unset($data['lead_ids']);
        $activity->update($data);
        $this->syncLeads($activity, $leadIds);
        return redirect()->route('activities.index')->with('success', $activity->activity_code.' updated.');
    }

    public function destroy(Request $request, CrmActivity $activity)
    {
        $this->authorizeActivity($request, $activity);
        $activity->delete();
        return redirect()->route('activities.index')->with('success', 'Activity deleted.');
    }

    public function status(Request $request, CrmActivity $activity)
    {
        $this->authorizeActivity($request, $activity);
        $data = $request->validate(['status' => ['required', Rule::in(['Delayed', 'ReScheduled', 'Complete'])], 'completion_comment' => ['nullable', 'string', 'max:10000']]);
        $activity->status = $data['status'];
        if ($data['status'] === 'Complete') {
            $activity->completed_at = now('Asia/Kolkata');
            $activity->completion_comment = $data['completion_comment'] ?? null;
        } else {
            $activity->completed_at = null;
        }
        $activity->save();
        return redirect()->route('activities.index', $request->boolean('create_new') ? ['new' => 1] : [])->with('success', $activity->activity_code.' marked '.$data['status'].'.');
    }

    private function data(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'activity_type' => ['required', Rule::in(CrmActivity::TYPES)],
            'scheduled_at' => ['required', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'lead_ids' => ['nullable', 'array', 'max:50'],
            'lead_ids.*' => ['integer', 'distinct', 'exists:leads,id'],
            'description' => ['nullable', 'string', 'max:10000'],
            'link' => ['nullable', 'url', 'max:2048'],
            'send_calendar_to_assignee' => ['nullable', 'boolean'],
            'send_calendar_to_client' => ['nullable', 'boolean'],
        ]);
        $data['send_calendar_to_assignee'] = $request->boolean('send_calendar_to_assignee');
        $data['send_calendar_to_client'] = $request->boolean('send_calendar_to_client');
        $data['send_calendar_invite'] = $data['send_calendar_to_assignee'] || $data['send_calendar_to_client'];
        $data['lead_ids'] = $data['lead_ids'] ?? [];
        $data['lead_id'] = $data['lead_ids'][0] ?? null;
        return $data;
    }

    private function syncLeads(CrmActivity $activity, array $leadIds): void
    {
        $activity->leads()->sync($leadIds);
    }

    private function authorizeActivity(Request $request, CrmActivity $activity): void
    {
        abort_unless($request->user()->isAdmin() || $activity->created_by === $request->user()->id || $activity->assigned_to === $request->user()->id, 403);
    }
}
