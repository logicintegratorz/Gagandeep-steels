<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\JobRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ContactController
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'job_role' => ['nullable', Rule::exists('job_roles', 'name')],
        ]);
        $query = Lead::query()
            ->whereNull('connection_id')
            ->with('owner:id,name');

        if (! $request->user()->allows('leads.view_all')) {
            $query->where(fn ($contacts) => $contacts
                ->where('owner_id', $request->user()->id)
                ->orWhere('created_by', $request->user()->id));
        }

        $contactCount = (clone $query)->count();
        $roleCounts = (clone $query)
            ->whereNotNull('job_role')
            ->selectRaw('job_role, COUNT(*) as total')
            ->groupBy('job_role')
            ->pluck('total', 'job_role');

        if (filled($data['job_role'] ?? null)) {
            $query->where('job_role', $data['job_role']);
        }

        if (filled($data['q'] ?? null)) {
            $term = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($data['q'])) . '%';
            $query->where(function ($contacts) use ($term) {
                foreach (['name', 'company', 'phone', 'alt_phone', 'whatsapp', 'email'] as $column) {
                    $contacts->orWhereRaw("{$column} LIKE ? ESCAPE '!'", [$term]);
                }
            });
        }

        return view('contacts.index', [
            // A platform import always has a connection. Showing every record
            // without one also keeps older manually-entered contacts visible.
            'contacts' => $query->orderByRaw('COALESCE(contact_saved_at, created_at) DESC')->paginate(25)->withQueryString(),
            'canViewAll' => $request->user()->allows('leads.view_all'),
            'jobRoles' => JobRole::query()->orderBy('name')->pluck('name'),
            'roleCounts' => $roleCounts,
            'contactCount' => $contactCount,
        ]);
    }
}
