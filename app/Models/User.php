<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

final class User extends Authenticatable
{
    use Notifiable;
    protected $attributes = ['version' => 1, 'session_version' => 1];
    protected $fillable = ['name', 'email', 'phone', 'avatar_path', 'password', 'role_id', 'active', 'inactivity_timeout_minutes'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array { return ['product_slots'=>'array', 'product_order'=>'array', 'password' => 'hashed', 'active' => 'boolean', 'session_version' => 'integer', 'version' => 'integer', 'last_login_at' => 'datetime', 'inactivity_timeout_minutes' => 'integer']; }
    public function role() { return $this->belongsTo(Role::class); }
    public function screenTimeEntries() { return $this->hasMany(ScreenTimeEntry::class); }
    public function loginEvents() { return $this->hasMany(UserLoginEvent::class); }
    public function allows(string $permission): bool
    {
        if (!$this->active || !$this->role) return false;
        if ($this->role->is_admin || in_array($permission, $this->role->permissions ?? [], true)) return true;

        // Roles configured before module-level Productivity permissions keep
        // their access after the permissions are split into individual tabs.
        if (str_starts_with($permission, 'productivity.')) {
            $legacy = $this->role->permissions ?? [];
            return in_array('workspace.productivity.manage', $legacy, true)
                || (str_ends_with($permission, '.view') && in_array('workspace.productivity.view', $legacy, true));
        }

        return false;
    }
    public function isAdmin(): bool { return $this->active && (bool) $this->role?->is_admin; }

    /** Return a usable public avatar only when its file still exists. */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar_path || !Storage::disk('public')->exists($this->avatar_path)) return null;

        return Storage::disk('public')->url($this->avatar_path);
    }
}
