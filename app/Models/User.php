<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'username', 'email', 'password', 'role', 'company_id', 'active'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['email_verified_at' => 'datetime', 'password' => 'hashed'];

    public function isSuperAdmin(): bool { return $this->role === 'superadmin'; }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isManager(): bool { return $this->role === 'manager'; }
    public function isStaff(): bool { return $this->role === 'staff'; }
    public function isAdminOrAbove(): bool { return in_array($this->role, ['superadmin', 'admin']); }
    public function isManagerOrAbove(): bool { return in_array($this->role, ['superadmin', 'admin', 'manager']); }

    public function company() { return $this->belongsTo(Company::class); }
    public function units() { return $this->belongsToMany(Unit::class, 'user_units'); }
    public function completedOccurrences() { return $this->hasMany(TaskOccurrence::class, 'completed_by'); }
    public function modulePermissions() { return $this->hasMany(UserModulePermission::class); }
}
