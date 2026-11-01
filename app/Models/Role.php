<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

final class Role extends Model
{
    protected $attributes = ['version' => 1];
    protected $fillable = ['name', 'description', 'permissions'];
    protected function casts(): array { return ['permissions' => 'array', 'is_admin' => 'boolean', 'version' => 'integer']; }
    public function getPermissionsAttribute($value): array
    {
        $permissions = json_decode($value ?: '[]', true);
        if (!is_array($permissions)) return [];
        if (in_array('leads.work', $permissions, true)) {
            $permissions = array_merge($permissions, ['leads.tasks', 'leads.followups', 'leads.reminders', 'leads.activity']);
        }
        return \App\Support\Permissions::expand($permissions);
    }
    public function users() { return $this->hasMany(User::class); }
}
