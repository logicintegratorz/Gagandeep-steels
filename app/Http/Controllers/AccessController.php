<?php
namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\CalendarAssignment;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

final class AccessController
{
    public function index(Request $request) { return $this->screen($request); }
    public function editUser(Request $request, User $user)
    {
        $this->canManage($request, $user->role);
        return $this->screen($request, $user);
    }
    public function editRole(Request $request, Role $role)
    {
        abort_if($role->is_admin, 403, 'The Administrator role is protected.');
        $this->canManage($request, $role);
        return $this->screen($request, null, $role);
    }
    private function screen(Request $request, ?User $editingUser = null, ?Role $editingRole = null)
    {
        $filters = $request->validate(['search' => ['nullable','string','max:150'], 'role' => ['nullable','integer'], 'status' => ['nullable',Rule::in(['active','inactive'])]]);
        $query = User::with('role')->orderBy('name');
        if (filled($filters['search'] ?? null)) {
            $term = '%'.str_replace(['!','%','_'], ['!!','!%','!_'], trim($filters['search'])).'%';
            $query->where(fn ($q) => $q->whereRaw("name LIKE ? ESCAPE '!'", [$term])->orWhereRaw("email LIKE ? ESCAPE '!'", [$term])->orWhereRaw("phone LIKE ? ESCAPE '!'", [$term]));
        }
        if (!empty($filters['role'])) $query->where('role_id', $filters['role']);
        if (!empty($filters['status'])) $query->where('active', $filters['status'] === 'active');
        $roles = Role::withCount('users')->orderByDesc('is_admin')->orderBy('name')->get();
        return view('access.index', [
            'users' => $request->user()->allows('users.view') ? $query->paginate(10)->withQueryString() : null,
            'roles' => $roles, 'assignableRoles' => $roles->filter(fn ($role) => $this->mayManage($request->user(), $role)),
            'filters' => $filters, 'editingUser' => $editingUser, 'editingRole' => $editingRole,
            'permissionGroups' => Permissions::groups(),
        ]);
    }
    private function mayManage(User $actor, Role $role): bool
    {
        return $actor->isAdmin() || (!$role->is_admin && empty(array_diff($role->permissions ?? [], $actor->role->permissions ?? [])));
    }
    private function canManage(Request $request, Role $role): void
    {
        abort_unless($this->mayManage($request->user(), $role), 403, 'You cannot manage a role or account with permissions above your own.');
    }
    private function canAssignNotificationRole(Request $request, Role $role): void
    {
        if (!$request->user()->isAdmin() && array_intersect(array_keys(Permissions::groups()['Notifications']), $role->permissions ?? [])) {
            abort(403, 'Only an Administrator can assign or remove a role with notification management permissions.');
        }
    }
    private function lockAdministration(): void
    {
        // A shared lock order prevents concurrent requests from removing all administrators.
        Role::where('is_admin', true)->lockForUpdate()->firstOrFail();
    }
    private function currentVersion(Request $request, $record): void
    {
        if ($record->version !== $request->integer('version')) throw ValidationException::withMessages(['conflict' => 'This record changed elsewhere. Reopen it before trying again.']);
    }
    private function userData(Request $request, ?User $user = null): array
    {
        if ($user && $request->filled('password')) abort_unless($request->user()->isAdmin(), 403, 'Only an Administrator can reset a user password.');
        if (is_string($request->email)) $request->merge(['email' => strtolower(trim($request->email)) ?: null]);
        if (is_string($request->phone)) {
            $phone = preg_replace('/\D+/', '', $request->phone);
            $request->merge(['phone' => $phone !== '' ? $phone : null]);
        }
        if (is_string($request->name)) $request->merge(['name' => trim($request->name)]);
        return $request->validate([
            'name' => ['required','string','max:100'],
            'email' => ['bail','nullable','required_without:phone','string','max:255',new \App\Rules\LoginEmail,Rule::unique('users')->ignore($user?->id)],
            'phone' => ['bail','nullable','required_without:email','string','min:7','max:20','regex:/^[0-9]+$/',Rule::unique('users')->ignore($user?->id)],
            'avatar' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
            'active' => ['required','boolean'], 'role_id' => [$user ? 'sometimes' : 'required','integer','exists:roles,id'],
            'password' => [$user ? 'nullable' : 'required','confirmed','max:255',PasswordRule::min(8)->letters()->numbers()],
            'version' => [$user ? 'required' : 'sometimes','integer','min:1'],
        ]);
    }
    public function storeUser(Request $request)
    {
        abort_unless($request->user()->allows('users.assign_roles'), 403);
        $data = $this->userData($request);
        $avatar=$data['avatar']??null;unset($data['avatar']);
        try {
            DB::transaction(function () use ($request, $data, $avatar) {
                $this->lockAdministration();
                $role = Role::whereKey($data['role_id'])->lockForUpdate()->firstOrFail();
                $this->canManage($request, $role);
                $this->canAssignNotificationRole($request, $role);
                $created = User::create($data);
                if($avatar){$created->avatar_path=$avatar->store('user-avatars','public');$created->save();}
                CalendarAssignment::create(['actor_id'=>$request->user()->id,'recipient_id'=>$created->id,'kind'=>'Role','title'=>'Role assigned: '.$role->name,'detail'=>'Your CRM access has been set to '.$role->name.'.','link'=>route('home'),'assigned_at'=>now()]);
            });
        } catch (UniqueConstraintViolationException $exception) { throw ValidationException::withMessages(['email' => 'This email is already in use.']); }
        return redirect()->route('access.index')->with('success', 'User created. Share the initial password securely with the user.');
    }
    public function updateUser(Request $request, User $user)
    {
        $data = $this->userData($request, $user);
        $avatar=$data['avatar']??null;unset($data['avatar']);
        if (empty($data['password'])) unset($data['password']);
        try {
            DB::transaction(function () use ($request, $user, $data, $avatar) {
                $this->lockAdministration();
                $current = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $this->currentVersion($request, $current); $this->canManage($request, $current->role);
                $roleId = $data['role_id'] ?? $current->role_id;
                if ($roleId != $current->role_id) abort_unless($request->user()->allows('users.assign_roles'), 403);
                $role = Role::whereKey($roleId)->lockForUpdate()->firstOrFail();
                $this->canManage($request, $role);
                if ($roleId != $current->role_id) {
                    $this->canAssignNotificationRole($request, $current->role);
                    $this->canAssignNotificationRole($request, $role);
                }
                if ($current->id === $request->user()->id && (!$data['active'] || $roleId != $current->role_id)) throw ValidationException::withMessages(['active' => 'You cannot deactivate yourself or change your own role. Ask another administrator.']);
                $this->protectLastAdmin($current, !$data['active'] || !$role->is_admin);
                $oldEmail = $current->email;
                $roleChanged = $roleId != $current->role_id;
                $current->fill($data);if($avatar){$oldAvatar=$current->avatar_path;$current->avatar_path=$avatar->store('user-avatars','public');if($oldAvatar)Storage::disk('public')->delete($oldAvatar);} $current->version++;
                if (isset($data['password']) || $oldEmail !== $current->email || !$current->active) $current->session_version++;
                $current->save();
                if ($roleChanged) CalendarAssignment::create(['actor_id'=>$request->user()->id,'recipient_id'=>$current->id,'kind'=>'Role','title'=>'Role assigned: '.$role->name,'detail'=>'Your CRM access has been changed to '.$role->name.'.','link'=>route('home'),'assigned_at'=>now()]);
                $emails=array_values(array_filter([$oldEmail,$current->email]));if($emails)DB::table('password_reset_tokens')->whereIn('email',$emails)->delete();
            });
        } catch (UniqueConstraintViolationException $exception) { throw ValidationException::withMessages(['email' => 'This email is already in use.']); }
        $message = isset($data['password']) ? 'User updated and password reset successfully. Share the new password securely. Existing sessions have been signed out.' : 'User updated. Role and permission changes apply on the user’s next action.';
        if ($user->id === $request->user()->id && (isset($data['password']) || $data['email'] !== $request->user()->email)) {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('status', 'Your account was updated. Sign in again using your updated credentials.');
        }
        return redirect()->route('access.index')->with('success', $message);
    }
    private function protectLastAdmin(User $user, bool $removingAccess): void
    {
        if ($removingAccess && $user->active && $user->role->is_admin && User::where('active', true)->whereHas('role', fn ($q) => $q->where('is_admin', true))->count() <= 1) {
            throw ValidationException::withMessages(['user' => 'At least one active Administrator must remain.']);
        }
    }
    public function destroyUser(Request $request, User $user)
    {
        $request->validate(['version' => ['required','integer','min:1']]);
        abort_if($user->id === $request->user()->id, 403, 'You cannot delete your own account.');
        DB::transaction(function () use ($request, $user) {
            $this->lockAdministration(); $current = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $this->currentVersion($request, $current); $this->canManage($request, $current->role); $this->protectLastAdmin($current, true);
            if($current->email)DB::table('password_reset_tokens')->where('email', $current->email)->delete();if($current->avatar_path)Storage::disk('public')->delete($current->avatar_path); $current->delete();
        });
        return redirect()->route('access.index')->with('success', 'User deleted. Their access has been revoked; business records are preserved.');
    }
    private function roleData(Request $request, ?Role $role = null): array
    {
        if (is_string($request->name)) $request->merge(['name' => trim($request->name)]);
        $data = $request->validate([
            'name' => ['required','string','max:100',Rule::unique('roles')->ignore($role?->id)],
            'description' => ['nullable','string','max:255'], 'permissions' => ['sometimes','array'],
            'permissions.*' => ['string','distinct',Rule::in(Permissions::keys())],
            'version' => [$role ? 'required' : 'sometimes','integer','min:1'],
        ]);
        $data['permissions'] = Permissions::expand($data['permissions'] ?? []);
        if (!$request->user()->isAdmin() && array_diff($data['permissions'], $request->user()->role->permissions ?? [])) abort(403, 'You cannot grant permissions you do not have.');
        $notificationKeys = array_keys(Permissions::groups()['Notifications']);
        $before = array_values(array_intersect($notificationKeys, $role?->permissions ?? []));
        $after = array_values(array_intersect($notificationKeys, $data['permissions']));
        if (!$request->user()->isAdmin() && $before !== $after) abort(403, 'Only an Administrator can assign or change notification management permissions.');
        return $data;
    }
    public function storeRole(Request $request)
    {
        $data = $this->roleData($request);
        try { Role::create($data); }
        catch (UniqueConstraintViolationException $exception) { throw ValidationException::withMessages(['name' => 'This role name is already in use.']); }
        return redirect()->route('access.index')->with('success', 'Role created. Assign it to a user to apply its permissions.');
    }
    public function updateRole(Request $request, Role $role)
    {
        abort_if($role->is_admin, 403, 'The Administrator role is protected.');
        $data = $this->roleData($request, $role);
        try {
            DB::transaction(function () use ($request, $role, $data) {
                $this->lockAdministration(); $current = Role::whereKey($role->id)->lockForUpdate()->firstOrFail();
                $this->canManage($request, $current); $this->currentVersion($request, $current);
                $current->fill($data); $current->version++; $current->save();
                $current->users()->update(['version' => DB::raw('version + 1')]);
            });
        } catch (UniqueConstraintViolationException $exception) { throw ValidationException::withMessages(['name' => 'This role name is already in use.']); }
        return redirect()->route('access.index')->with('success', 'Role updated. Assigned users’ permissions apply on their next action.');
    }
    public function destroyRole(Request $request, Role $role)
    {
        abort_if($role->is_admin, 403, 'The Administrator role is protected.');
        $request->validate(['version' => ['required','integer','min:1']]);
        DB::transaction(function () use ($request, $role) {
            $this->lockAdministration(); $current = Role::whereKey($role->id)->lockForUpdate()->firstOrFail();
            $this->canManage($request, $current); $this->currentVersion($request, $current);
            if ($current->users()->exists()) throw ValidationException::withMessages(['role' => 'Reassign all users of this role before deleting it.']);
            $current->delete();
        });
        return redirect()->route('access.index')->with('success', 'Role deleted.');
    }
}
