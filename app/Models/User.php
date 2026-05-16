<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role',
        'company_id', 'work_schedule_id', 'active',
        'pin', 'pin_reset_required',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    public function isSuperAdmin(): bool  { return $this->role === 'superadmin'; }
    public function isAdmin(): bool       { return $this->role === 'admin'; }
    public function isManager(): bool     { return $this->role === 'manager'; }
    public function isStaff(): bool       { return $this->role === 'staff'; }
    public function isAdminOrAbove(): bool   { return in_array($this->role, ['superadmin', 'admin']); }
    public function isManagerOrAbove(): bool { return in_array($this->role, ['superadmin', 'admin', 'manager']); }

    /**
     * IDs das unidades visíveis para o usuário.
     * Admin/superadmin: null (sem filtro — vê tudo da empresa).
     * Manager/staff: apenas as unidades atribuídas via user_units.
     */
    public function visibleUnitIds(): ?array
    {
        if ($this->isAdminOrAbove()) {
            return null;
        }
        return $this->units()->pluck('units.id')->toArray();
    }

    public function company()              { return $this->belongsTo(Company::class); }
    public function workSchedule()         { return $this->belongsTo(WorkSchedule::class); }
    public function units()                { return $this->belongsToMany(Unit::class, 'user_units'); }
    public function completedOccurrences() { return $this->hasMany(TaskOccurrence::class, 'completed_by'); }
    public function modulePermissions()    { return $this->hasMany(UserModulePermission::class); }
    public function shifts()               { return $this->hasMany(Shift::class); }
    public function timeEntries()          { return $this->hasMany(TimeEntry::class); }
}