<?php

namespace App\Http\Controllers;

use App\Models\{JobRole, Lead};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class JobRoleController
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('leads.job-roles', [
            'jobRoles' => JobRole::orderBy('name')->get(),
            'usage' => Lead::withTrashed()->whereNotNull('job_role')->selectRaw('job_role, COUNT(*) as total')->groupBy('job_role')->pluck('total', 'job_role'),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('job_roles', 'name')]]);
        JobRole::create($data);

        return redirect()->route('leads.job-roles')->with('success', 'Job roll added. It is now available in lead forms and filters.');
    }

    public function update(Request $request, JobRole $jobRole)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('job_roles', 'name')->ignore($jobRole->id)]]);

        DB::transaction(function () use ($jobRole, $data) {
            $role = JobRole::lockForUpdate()->findOrFail($jobRole->id);
            if ($role->name === $data['name']) return;
            Lead::withTrashed()->where('job_role', $role->name)->update(['job_role' => $data['name'], 'version' => DB::raw('version + 1')]);
            $role->update(['name' => $data['name']]);
        });

        return redirect()->route('leads.job-roles')->with('success', 'Job roll updated in all linked leads and dropdowns.');
    }

    public function destroy(Request $request, JobRole $jobRole)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['replacement_id' => ['nullable', 'integer', Rule::exists('job_roles', 'id')]]);

        DB::transaction(function () use ($jobRole, $data) {
            $role = JobRole::lockForUpdate()->findOrFail($jobRole->id);
            $linked = Lead::withTrashed()->where('job_role', $role->name);
            if ($linked->exists()) {
                $replacementId = (int) ($data['replacement_id'] ?? 0);
                if (!$replacementId || $replacementId === $role->id) {
                    throw ValidationException::withMessages(['replacement_id' => 'Choose a different job roll for linked leads before deleting.']);
                }
                $replacement = JobRole::whereKey($replacementId)->firstOrFail();
                $linked->update(['job_role' => $replacement->name, 'version' => DB::raw('version + 1')]);
            }
            $role->delete();
        });

        return redirect()->route('leads.job-roles')->with('success', 'Job roll deleted. Linked leads were moved to the selected roll.');
    }
}
