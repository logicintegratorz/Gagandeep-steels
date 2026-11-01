<?php
namespace App\Http\Controllers;

use App\Models\{Lead, LeadActivity, LeadWorkItem, User};
use Illuminate\Http\Request;
use App\Support\DashboardContacts;

final class DashboardMetricController
{
    public function __invoke(Request $request, string $metric)
    {
        $labels = [
            'todays-leads' => 'Today’s Leads', 'uncontacted-leads' => 'Uncontacted Leads',
            'opened-leads' => 'Opened Leads', 'converted-leads' => 'Converted Leads',
            'site-visits' => 'Site Visits', 'manual-leads' => 'Manual Leads',
            'overdue-tasks' => 'Overdue Tasks', 'my-tasks' => 'My Tasks',
            'my-overdue-tasks' => 'My Overdue Tasks', 'initial-contact' => 'Initial Contact',
            'enquiry-confirmed' => 'Enquiry Confirmed', 'negotiation' => 'Negotiation',
            'site-approval' => 'Site Approval', 'paperwork' => 'Paperwork',
            'all-reminders' => 'All Reminders', 'my-reminders' => 'My Reminders',
            'follow-ups' => 'Follow-ups', 'due-today' => 'Due Today',
            'outgoing-calls' => 'Outgoing Calls', 'incoming-calls' => 'Incoming Calls',
            'missed-calls' => 'Missed Calls',
        ];
        abort_unless(isset($labels[$metric]), 404);
        $type = 'leads';
        $filters = [];
        $companies = collect();
        $users = collect();
        $perPage=(int)$request->input('per_page',25);
        if(!in_array($perPage,[25,50,100],true))$perPage=25;
        if (str_ends_with($metric, 'calls')) {
            abort_unless($request->user()->isAdmin(), 403);
            $type = 'calls';
            $filters = $request->validate([
                'company' => ['nullable', 'string', 'max:255'],
                'user' => ['nullable', 'integer', 'exists:users,id'],
                'from' => ['nullable', 'date_format:Y-m-d'],
                'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            ]);
            $direction = match ($metric) {
                'incoming-calls' => 'Incoming call%',
                'missed-calls' => 'Missed call%',
                default => 'Outgoing call%',
            };
            $query = LeadActivity::query()->where('kind', 'Call log')
                ->where('action', 'like', $direction)
                ->with('lead', 'actorUser.role')->latest();
            $companyQuery = LeadActivity::query()
                ->join('leads', 'leads.id', '=', 'lead_activities.lead_id')
                ->where('lead_activities.kind', 'Call log')
                ->where('lead_activities.action', 'like', $direction)
                ->whereNotNull('leads.company')->where('leads.company', '<>', '');
            if (filled($filters['user'] ?? null)) {
                $companyQuery->where('lead_activities.actor_id', (int) $filters['user']);
            }
            if (filled($filters['from'] ?? null)) {
                $companyQuery->whereDate('lead_activities.created_at', '>=', $filters['from']);
            }
            if (filled($filters['to'] ?? null)) {
                $companyQuery->whereDate('lead_activities.created_at', '<=', $filters['to']);
            }
            $companies = $companyQuery->select('leads.company')->distinct()->orderBy('leads.company')->pluck('leads.company');
            $users = User::query()->with('role')->where('active', true)->orderBy('name')->get();
            if (filled($filters['company'] ?? null)) {
                $query->whereHas('lead', fn ($lead) => $lead->where('company', $filters['company']));
            }
            if (filled($filters['user'] ?? null)) {
                $query->where('actor_id', (int) $filters['user']);
            }
            if (filled($filters['from'] ?? null)) {
                $query->whereDate('created_at', '>=', $filters['from']);
            }
            if (filled($filters['to'] ?? null)) {
                $query->whereDate('created_at', '<=', $filters['to']);
            }
        } elseif (in_array($metric, ['overdue-tasks', 'my-tasks', 'my-overdue-tasks', 'all-reminders', 'my-reminders', 'follow-ups', 'due-today'], true)) {
            $type = 'work';
            $permission = $metric === 'follow-ups' ? 'leads.followups' : (str_contains($metric, 'task') ? 'leads.tasks' : 'leads.reminders');
            abort_unless($metric === 'due-today'
                ? ($request->user()->allows('leads.reminders') || $request->user()->allows('leads.followups'))
                : $request->user()->allows($permission), 403);
            $query = LeadWorkItem::visibleTo($request->user())->where('status', 'Pending')->with('lead', 'assignee');
            if (str_contains($metric, 'task')) $query->where('kind', 'Task');
            elseif ($metric === 'follow-ups') $query->where('kind', 'Follow-up');
            elseif ($metric !== 'due-today') $query->where('kind', 'Reminder');
            if (in_array($metric, ['overdue-tasks', 'my-overdue-tasks'], true)) $query->where('due_at', '<', now());
            if ($metric === 'due-today') $query->whereDate('due_at', today());
            if (in_array($metric, ['my-tasks', 'my-overdue-tasks', 'my-reminders'], true)) $query->where('assigned_to', $request->user()->id);
            $query->orderBy('due_at');
        } else {
            $request->validate(['filters'=>'nullable|array','per_page'=>'nullable|integer']);
            $query = DashboardContacts::query($request)->latest();
            match ($metric) {
                'todays-leads' => $query->whereDate('created_at', today()),
                'uncontacted-leads' => $query->where('stage', 'New'),
                'opened-leads' => $query->whereNotIn('stage', ['Won', 'Lost']),
                'converted-leads' => $query->where('stage', 'Won'),
                'site-visits', 'site-approval' => $query->where('stage', 'Site Approval'),
                'manual-leads' => $query->whereNull('connection_id'),
                default => $query->where('stage', $labels[$metric]),
            };
        }
        return view('dashboard-metric', [
            'title' => $labels[$metric],
            'type' => $type,
            'records' => $query->paginate($perPage)->withQueryString(),
            'filters' => $filters,
            'companies' => $companies,
            'users' => $users,
            'perPage' => $perPage,
            'scoreSettings' => $type === 'leads' ? \App\Models\LeadSetting::find(1) : null,
        ]);
    }
}
