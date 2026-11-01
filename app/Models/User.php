<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class User extends Authenticatable
{
    use Notifiable;
    protected $attributes = ['version' => 1, 'session_version' => 1];
    protected $fillable = ['name', 'email', 'phone', 'avatar_path', 'password', 'role_id', 'active'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array { return ['product_slots'=>'array', 'product_order'=>'array', 'password' => 'hashed', 'active' => 'boolean', 'session_version' => 'integer', 'version' => 'integer', 'last_login_at' => 'datetime']; }
    public function role() { return $this->belongsTo(Role::class); }
    public function screenTimeEntries() { return $this->hasMany(ScreenTimeEntry::class); }
    public function loginEvents() { return $this->hasMany(UserLoginEvent::class); }
    public function allows(string $permission): bool
    {
        return $this->active && $this->role && ($this->role->is_admin || in_array($permission, $this->role->permissions ?? [], true));
    }
    public function isAdmin(): bool { return $this->active && (bool) $this->role?->is_admin; }
}
